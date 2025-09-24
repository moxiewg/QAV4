<?php
// QA-Infowallet Admin Panel (single-file implementation)
// Recreated to match the provided feature list.

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

// --- Authentication Check ---
// If the user is not authenticated, redirect to the login page and stop execution.
if (empty($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit;
}


// --- Configuration ---
$ROOT = __DIR__ . DIRECTORY_SEPARATOR;
$DATA_FILE = $ROOT . 'data.json';
$UPLOADS_DIR = $ROOT . 'assets' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR;
$BACKUP_DIR = $ROOT . 'backups' . DIRECTORY_SEPARATOR;
$MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
$ALLOWED_MIME = ['image/jpeg','image/png','image/gif','image/svg+xml'];
$ALLOWED_EXT = ['jpg','jpeg','png','gif','svg'];

foreach ([$UPLOADS_DIR, $BACKUP_DIR] as $d) { if (!is_dir($d)) @mkdir($d, 0755, true); }

// --- Helpers ---
function jsonResponse(array $data) { header('Content-Type: application/json'); echo json_encode($data); exit; }
function log_error(string $msg, $ctx = []) { error_log('['.date('c').'] admin.php ERROR: '.$msg.' '.json_encode($ctx)); }
function csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf(?string $token): bool { return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token); }
// Backwards-compatible wrappers for older callers
function generateCSRFToken(): string { return csrf_token(); }
function verifyCSRFToken($token): bool { return verify_csrf($token); }
function uid(string $prefix='id'): string { return $prefix.'_'.bin2hex(random_bytes(6)); }
function safeReadJson(string $path): array { if (!file_exists($path)) return ['categories'=>[], 'metadata'=>['version'=>'1.0']]; $c = @file_get_contents($path); if ($c === false) return ['categories'=>[], 'metadata'=>['version'=>'1.0']]; $d = json_decode($c, true); return is_array($d) ? $d : ['categories'=>[], 'metadata'=>['version'=>'1.0']]; }
function safeWriteJson(string $path, array $data): bool { global $BACKUP_DIR; $data['metadata']['modified'] = date('c'); if (file_exists($path)) { $bk = $BACKUP_DIR . 'data_backup_'.date('Y-m-d_H-i-s').'.json'; @copy($path, $bk); $b = glob($BACKUP_DIR.'*.json'); if (count($b) > 10) { usort($b, fn($a,$z)=>filemtime($a)-filemtime($z)); foreach(array_slice($b,0,count($b)-10) as $old) @unlink($old); } } $tmp = $path.'.tmp'; if (@file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX) === false) return false; return @rename($tmp, $path); }
function sanitize_string($v): string { return htmlspecialchars(trim((string)($v ?? '')), ENT_QUOTES, 'UTF-8'); }
function sanitize_slug($v): string { $s = preg_replace('/[^a-z0-9_-]/','', strtolower((string)$v)); return $s; }
function sanitize_bool($v): bool { return filter_var($v, FILTER_VALIDATE_BOOLEAN); }
function sanitize_color($v): string { return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$v) ? (string)$v : '#ff5001'; }

// --- DataManager ---
class DataManager {
        private string $path;
        public function __construct(string $path) { $this->path = $path; }
        public function get(): array { $d = safeReadJson($this->path); // normalize
                $d['categories'] = array_values(array_filter(array_map(function($c){ if (!is_array($c)) return null; $c['id'] = $c['id'] ?? uid('cat'); $c['name'] = $c['name'] ?? 'Untitled'; $c['items'] = array_values(array_filter($c['items'] ?? [], fn($i)=>is_array($i))); return $c; }, $d['categories'])));
                return $d;
        }
        public function save(array $data): bool { return safeWriteJson($this->path, $data); }
}

// --- File upload helper ---
function handle_upload(array $file): array { global $UPLOADS_DIR, $MAX_FILE_SIZE, $ALLOWED_MIME, $ALLOWED_EXT; if (empty($file) || $file['error']===UPLOAD_ERR_NO_FILE) return ['success'=>true,'path'=>null]; if ($file['error']!==UPLOAD_ERR_OK) return ['success'=>false,'message'=>'Upload error code '.$file['error']]; if ($file['size']>$MAX_FILE_SIZE) return ['success'=>false,'message'=>'File too large']; $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo); if (!in_array($mime, $ALLOWED_MIME)) return ['success'=>false,'message'=>'Invalid MIME type']; $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); if (!in_array($ext, $ALLOWED_EXT)) return ['success'=>false,'message'=>'Invalid file extension']; if ($ext==='svg') { $contents = file_get_contents($file['tmp_name']); if (stripos($contents,'<script')!==false) return ['success'=>false,'message'=>'Unsafe SVG']; }
        $name = uid('icon').'.'.$ext; $dest = $UPLOADS_DIR.$name; if (!move_uploaded_file($file['tmp_name'],$dest)) return ['success'=>false,'message'=>'Failed to move file']; @chmod($dest, 0644); return ['success'=>true,'path'=>'assets/icons/'.$name]; }

// --- Router for AJAX ---
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') {
        $dm = new DataManager($DATA_FILE);
        try {
                $csrf = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
                $action = $_REQUEST['action'] ?? '';
                if (!in_array($action, ['getData','getIcons']) && !verify_csrf($csrf)) throw new Exception('Invalid CSRF token');
                switch ($action) {
                        case 'getData': jsonResponse(['success'=>true,'data'=>$dm->get()]); break;
                        case 'getIcons': $files = []; foreach (glob($UPLOADS_DIR.'*') as $f) { $ext = strtolower(pathinfo($f,PATHINFO_EXTENSION)); if (in_array($ext,$ALLOWED_EXT)) $files[] = 'assets/icons/'.basename($f); } jsonResponse(['success'=>true,'icons'=>$files]); break;
                        case 'saveCategory':
                                $isEdit = sanitize_bool($_POST['isEdit'] ?? false);
                                $name = sanitize_string($_POST['name'] ?? '');
                                $id = sanitize_slug($_POST['id'] ?? '');
                                $orig = sanitize_slug($_POST['originalId'] ?? '');
                                if ($name==='') jsonResponse(['success'=>false,'message'=>'Category name required']);
                                $data = $dm->get(); if ($isEdit) { $found=false; foreach ($data['categories'] as &$c) { if ($c['id']===$orig) { $c['name']=$name; $found=true; break; } } if (!$found) jsonResponse(['success'=>false,'message'=>'Category not found']); } else { if ($id==='') jsonResponse(['success'=>false,'message'=>'Category id required']); foreach ($data['categories'] as $c) if ($c['id']===$id) jsonResponse(['success'=>false,'message'=>'ID already exists']); $data['categories'][]=['id'=>$id,'name'=>$name,'items'=>[]]; }
                                if ($dm->save($data)) jsonResponse(['success'=>true,'message'=>'Category saved','data'=>$data]); else jsonResponse(['success'=>false,'message'=>'Failed to save']);
                                break;
                        case 'deleteCategory':
                                $id = sanitize_slug($_POST['id'] ?? ''); if ($id==='') jsonResponse(['success'=>false,'message'=>'ID required']); $data = $dm->get(); $before = count($data['categories']); $data['categories']=array_values(array_filter($data['categories'], fn($c)=>$c['id']!==$id)); if (count($data['categories'])===$before) jsonResponse(['success'=>false,'message'=>'Category not found']); if ($dm->save($data)) jsonResponse(['success'=>true,'message'=>'Deleted','data'=>$data]); else jsonResponse(['success'=>false,'message'=>'Failed to save']);
                                break;
                        case 'saveItem':
                                // Handle file upload first
                                $upload = handle_upload($_FILES['icon'] ?? []);
                                if (!$upload['success']) jsonResponse($upload);
                                $id = sanitize_string($_POST['id'] ?? ''); $categoryId = sanitize_slug($_POST['categoryId'] ?? ''); $title = sanitize_string($_POST['title'] ?? ''); $description = $_POST['description'] ?? ''; $value = sanitize_string($_POST['value'] ?? ''); $existingIcon = sanitize_string($_POST['existingIcon'] ?? ''); $color = sanitize_color($_POST['color'] ?? '#ff5001'); $allowQr = sanitize_bool($_POST['allowQr'] ?? false); $allowShare = sanitize_bool($_POST['allowShare'] ?? false);
                                if ($categoryId==='' || $title==='' || $value==='') jsonResponse(['success'=>false,'message'=>'Category, title and value required']);
                                $data = $dm->get(); $foundCat=false; foreach ($data['categories'] as &$c) {
                                        if ($c['id']===$categoryId) { $foundCat=true; if (!isset($c['items'])) $c['items']=[]; $itemId = $id ?: uid('item'); $new = ['id'=>$itemId,'title'=>$title,'description'=>$description,'value'=>$value,'icon'=>$upload['path'] ?? $existingIcon,'color'=>$color,'allow_qr'=>$allowQr,'allow_share'=>$allowShare]; $updated=false; foreach ($c['items'] as &$it) { if ($it['id']===$itemId) { // replace
                                                                                        // If the item's previous icon changed, attempt to remove the old file
                                                                                        // but avoid deleting icons that live in the shared assets pool.
                                                                                        if (!empty($it['icon']) && ($new['icon']!==$it['icon'])) {
                                                                                            $old = __DIR__ . '/' . ltrim($it['icon'],'/');
                                                                                            // Do not unlink files from the shared icons folder to avoid removing pool assets
                                                                                            if (file_exists($old) && strpos($it['icon'],'assets/icons/') === false) @unlink($old);
                                                                                        }
                                                                $it=$new; $updated=true; break; } }
                                                if (!$updated) $c['items'][]=$new; break; }
                                }
                                if (!$foundCat) jsonResponse(['success'=>false,'message'=>'Category not found']); if ($dm->save($data)) jsonResponse(['success'=>true,'message'=>'Item saved','data'=>$data]); else jsonResponse(['success'=>false,'message'=>'Failed to save']);
                                break;
                        case 'deleteItem':
                                $catId = sanitize_slug($_POST['categoryId'] ?? ''); $itemId = sanitize_string($_POST['id'] ?? ''); if ($catId===''||$itemId==='') jsonResponse(['success'=>false,'message'=>'Category and item id required']); $data = $dm->get(); $found=false; foreach ($data['categories'] as &$c) { if ($c['id']===$catId) { $before=count($c['items'] ?? []); $c['items']=array_values(array_filter($c['items'] ?? [], function($it) use ($itemId) { return ($it['id'] ?? '') !== $itemId; })); if (count($c['items'])<$before) $found=true; break; } } if (!$found) jsonResponse(['success'=>false,'message'=>'Item not found']); if ($dm->save($data)) jsonResponse(['success'=>true,'message'=>'Item deleted','data'=>$data]); else jsonResponse(['success'=>false,'message'=>'Failed to save']);
                                break;
                        case 'saveOrder':
                                // reorder categories or items
                                $type = sanitize_string($_POST['type'] ?? ''); $order = $_POST['order'] ?? [];
                                if (!in_array($type,['categories','items'])) jsonResponse(['success'=>false,'message'=>'Invalid type']); $data = $dm->get(); if ($type==='categories') { $new = []; foreach ($order as $id) { foreach ($data['categories'] as $c) if ($c['id']===$id) { $new[]=$c; break; } } $data['categories']=$new; if ($dm->save($data)) jsonResponse(['success'=>true,'message'=>'Order saved','data'=>$data]); else jsonResponse(['success'=>false,'message'=>'Failed to save']); }
                                // items: order should be { categoryId: '', order: [ids...] }
                                $categoryId = sanitize_slug($_POST['categoryId'] ?? ''); $ids = $order; if ($categoryId==='') jsonResponse(['success'=>false,'message'=>'Category required']); foreach ($data['categories'] as &$c) { if ($c['id']===$categoryId) { $map = []; foreach ($c['items'] ?? [] as $it) $map[$it['id']] = $it; $new = []; foreach ($ids as $id) if (isset($map[$id])) $new[]=$map[$id]; // append any missing
                                                foreach ($c['items'] ?? [] as $it) if (!isset($map[$it['id']])) $new[]=$it; $c['items']=$new; break; } }
                                if ($dm->save($data)) jsonResponse(['success'=>true,'message'=>'Order saved','data'=>$data]); else jsonResponse(['success'=>false,'message'=>'Failed to save']);
                                break;
            case 'moveItem':
                $from = sanitize_slug($_POST['fromCategory'] ?? '');
                $to = sanitize_slug($_POST['toCategory'] ?? '');
                $itemId = sanitize_string($_POST['itemId'] ?? '');
                if ($from===''||$to===''||$itemId==='') jsonResponse(['success'=>false,'message'=>'Missing parameters']);
                $data = $dm->get(); $foundItem = null;
                foreach ($data['categories'] as &$c) {
                    if ($c['id']===$from) {
                        foreach ($c['items'] ?? [] as $k => $it) {
                            if (($it['id'] ?? '') === $itemId) { $foundItem = $it; array_splice($c['items'], $k, 1); break 2; }
                        }
                    }
                }
                if (!$foundItem) jsonResponse(['success'=>false,'message'=>'Item not found']);
                foreach ($data['categories'] as &$c) { if ($c['id']===$to) { if (!isset($c['items'])) $c['items']=[]; $c['items'][]=$foundItem; break; } }
                if ($dm->save($data)) jsonResponse(['success'=>true,'message'=>'Moved','data'=>$data]); else jsonResponse(['success'=>false,'message'=>'Failed to save']);
                break;
                        default: jsonResponse(['success'=>false,'message'=>'Unknown action']);
                }
        } catch (Exception $e) { log_error($e->getMessage()); jsonResponse(['success'=>false,'message'=>'Server error']); }
}

$token = csrf_token();
?>
<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>QA-Infowallet — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://unpkg.com/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
     <link rel="icon" type="image/png" href="qa-fav.png">
    <style>
        [x-cloak] { display: none !important; }
    :root{--p:#ff5001;--card:#fff;--muted:#6b7280;--bg-body:#fff6f2;--text-primary:#4d1800;--card-bg:#ffffff;--bg-header:#ffffff}
    html.dark { --bg-body: #02022b; --text-primary: #e0e0ff; --card-bg: #0a0a4c; --bg-header:#0a0a4c }
    /* prefer Syne everywhere for consistent typography */
    body{font-family:'Syne',Inter,system-ui;background:var(--bg-body);color:var(--text-primary);transition:background .2s,color .2s}
        .syne { font-family: 'Syne', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
        .ghost{background:transparent;border:1px solid transparent;border-radius:9999px;padding:.4rem}
        .card{background:var(--card);border-radius:.8rem;box-shadow:0 6px 18px rgba(0,0,0,.06);overflow:hidden;transition: all 0.5s ease-in-out;}
    /* item card: taller minimum height for clearer layout */
    .item-card{display:flex;align-items:center;min-height:150px;border-radius:.6rem}
     .item-card:hover{ box-shadow: rgba(255, 81, 1, 0.87) 0px 12px 20px 4px;
    }
     .card:hover{
        box-shadow: rgba(255, 81, 1, 0.47) 0px 7px 29px 0px;
        transform: scale(1.005);
        border:1px solid #ff5001;
        
     }
        .item-icon-wrap{flex:0 0 96px;height:100%;display:flex;align-items:center;justify-content:center}
        .item-content{flex:1;padding:12px 14px;display:flex;flex-direction:column;justify-content:center}
        .item-title{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;margin:0}
    .item-value{font-family:'Syne',sans-serif;font-weight:400;font-size:0.7em;opacity:0.95}
        .meta-icons { display:flex;gap:8px;align-items:center }
        .meta-icons i { font-size:14px }
    .icon-only{width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px}
    /* FAB styling: perfect circle */
    .fab-btn{width:56px;height:56px;display:inline-flex;align-items:center;justify-content:center;border-radius:9999px}
    /* action button base for consistent stroke and hover/focus outline */
    /* .action-btn{background:transparent;border-radius:10px;padding:6px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:box-shadow .15s ease, transform .12s ease;} */
    .action-btn{border-radius: 95px;
  padding: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: box-shadow .15s ease, transform .12s ease;
  font-size: 16px;}
    .action-btn:focus{outline:none;transform:translateY(-1px)}
    .action-btn:focus-visible{box-shadow:0 0 0 4px rgba(0,0,0,0.12)}
    .action-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.16)}
    /* subtle blur / shadow behind action buttons to lift above busy images */
    .action-btn-shadow{box-shadow:0 6px 18px rgba(0,0,0,0.12);backdrop-filter:blur(6px)}
        .modal {position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:1.25rem}
        .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45)}
        /* icon browser layout tweaks */
        .icons-grid { display:grid;grid-template-columns:repeat(8,1fr);gap:12px;max-height:60vh;overflow:auto;padding:6px }
        @media(max-width:900px){ .icons-grid{grid-template-columns:repeat(6,1fr)} }
    @media(max-width:600px){ .icons-grid{grid-template-columns:repeat(4,1fr)} }
    /* Make modal panels follow theme variables */
    .modal .bg-white { background: var(--card-bg) !important; color: var(--text-primary) !important; }
    .modal .shadow { box-shadow: 0 6px 18px rgba(0,0,0,0.06) !important; }
    /* SweetAlert2 theme alignment */
    .swal2-popup { background: var(--card-bg) !important; color: var(--text-primary) !important; font-family: 'Syne', sans-serif !important; }
    html.dark .swal2-popup { background: var(--card-bg) !important; color: var(--text-primary) !important; }
    /* Improve form / input contrast and card text visibility */
    .card { position: relative; background: var(--card-bg); color: var(--text-primary); }
    input, select, textarea { background: var(--card-bg); color: var(--text-primary); border: 1px solid rgba(16,24,40,0.06); padding: .5rem; border-radius: .375rem; }
    input:focus, select:focus, textarea:focus { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--p) 14%, transparent); }
    /* Ghost icon buttons: make them visible against both light/dark and colored card backgrounds */
    .ghost.icon-only { background: rgba(255,255,255,0.7); color: #111; border: 1px solid rgba(0,0,0,0.04); box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
    html.dark .ghost.icon-only { background: rgba(0,0,0,0.36); color: #fff; border: 1px solid rgba(255,255,255,0.04); }
    /* When item card sets its own background color we prefer semi-transparent button backgrounds; style may be adjusted inline for exact contrast */
    /* Global action spinner overlay */
    #globalSpinner{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:60; }
    #globalSpinner .back{ position:absolute; inset:0; background:rgba(0,0,0,0.35); backdrop-filter:blur(2px);} 
    #globalSpinner .box{ position:relative; padding:18px 22px; border-radius:10px; background:var(--card-bg); color:var(--text-primary); display:flex;align-items:center;gap:12px;box-shadow:0 10px 40px rgba(0,0,0,0.18)}
    .spinner { width:28px;height:28px;border-radius:50%;border:3px solid rgba(0,0,0,0.08);border-top-color:var(--p);animation:spin 0.9s linear infinite }
    @keyframes spin{ from{transform:rotate(0)} to{transform:rotate(360deg)} }
            .qa-logo {
            display: inline-block;
            width: 50px;
            height: 50px;
            vertical-align: middle;
            margin-right: 0.75rem;
        }

        .ql-editor{
            min-height: 125px;
        }
        #fab-add-category, #fab-add-item {
            background: var(--card-bg);
            color: var(--text-primary);
        }
    </style>
</head>
<body x-data="adminApp()" x-init="init()" class="antialiased">
    <div class="sticky top-0 p-4 shadow-sm z-20 flex items-center justify-between" style="background:var(--bg-header)">
        <div class="flex items-center">
            <a href="index.php" class="flex items-center">
                   <img src="qaicon.svg" alt="QA Icon" class="qa-logo">         
            </a>

            <div class="flex-grow max-w-xl mx-4">
                <input x-model="searchQuery" @input.debounce.250="runSearch" @click="tab='items'" placeholder="Search items..." class="w-full p-3 px-6 rounded-md border" />
               
            </div>
        </div>

          <div class="flex items-center space-x-4">
              <button type="button" @click="theme.toggle()" id="theme-toggle" class="p-2 rounded-md"><i class="fas fa-sun light-icon hidden"></i><i class="fas fa-moon dark-icon"></i></button>
              <a href="logout.php" title="Logout" class="p-2 rounded-md text-red-500 hover:bg-red-100"><i class="fas fa-sign-out-alt"></i></a>
          </div>
    </div>

    <main class="p-6">
        <div class="flex items-center space-x-4 mb-6"><button :class="tab==='categories' ? 'font-bold' : ''" @click="tab='categories'">Categories</button><button :class="tab==='items' ? 'font-bold' : ''" @click="tab='items'">Items</button></div>

        <section x-show="tab==='categories'">
            <div id="categoriesList" class="space-y-3"></div>
        </section>

        <section x-show="tab==='items'" x-cloak>
            <div class="border-b border-border-color mb-6">
            <div class="mb-4 flex items-center " style="gap:8px">
                <div style="flex:0 0 90%"><select x-model="filterCategory" @change="renderItems()" class="w-full p-2 border rounded"><option value="">All Categories</option><template x-for="c in data.categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template></select></div>
                <div style="flex:0 0 10%"><button type="button" @click="filterCategory=''; renderItems();" class="p-2 border rounded w-full">Reset</button></div>
            </div>
            </div>
            <div id="itemsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
        </section>
    </main>

    <!-- FAB -->
        <div class="fixed bottom-6 right-6 flex flex-col items-end space-y-3">
        <div x-show="fabOpen" class="flex flex-col space-y-2">
            <button id="fab-add-category" @click="openCategoryModal()" class="bg-white p-3 rounded-md shadow">Add Category</button>
            <button id="fab-add-item" @click="openItemModal()" class="bg-white p-3 rounded-md shadow">Add Item</button>
        </div>
        <button @click="fabOpen = !fabOpen" class="bg-orange-500 text-white fab-btn shadow"><i class="fas fa-plus"></i></button>
    </div>

    <!-- Global Spinner Overlay -->
    <div id="globalSpinner">
        <div class="back"></div>
        <div class="box">
            <div class="spinner" aria-hidden="true"></div>
            <div id="globalSpinnerText">Working...</div>
        </div>
    </div>

    <!-- Modals (category/item/icon/move) -->
    <template x-if="showCategoryModal">
        <div class="modal">
            <div class="modal-backdrop" @click.self="closeAllModals()"></div>
            <div class="bg-white p-4 rounded shadow w-full max-w-md z-30">
                <h3 x-text="categoryModalTitle"></h3>
                <div class="mt-3 space-y-2"><input x-model="categoryForm.name" placeholder="Name" class="w-full p-2 border rounded" /><input x-model="categoryForm.id" placeholder="ID (lowercase)" class="w-full p-2 border rounded" /></div>
                <div class="mt-3 flex justify-end space-x-2"><button type="button" @click="closeAllModals()" class="p-2">Cancel</button><button @click="saveCategory()" class="p-2 bg-orange-500 text-white">Save</button></div>
            </div>
        </div>
    </template>

    <template x-if="showItemModal">
        <div class="modal">
            <div class="modal-backdrop" @click.self="closeAllModals()"></div>
            <div class="bg-white p-4 rounded shadow w-full max-w-2xl z-30">
                <h3 x-text="itemModalTitle"></h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                    <div><label>Category</label><select x-model="itemForm.categoryId" @change="onCategoryChange($event.target.value)" class="w-full p-2 border rounded"><template x-for="c in data.categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template></select></div>
                    <div><label>Title</label><input x-model="itemForm.title" class="w-full p-2 border rounded" /></div>
                </div>
                <div class="mt-3"><label>Value</label><input x-model="itemForm.value" class="w-full p-2 border rounded" /></div>
                <div class="mt-3"><label>Description</label><div id="quillContainer"></div></div>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <div>
                        <label>Icon</label>
                        <div class="flex items-center space-x-4">
                            <div id="iconPreview" class="h-16 w-16 rounded-md flex items-center justify-center overflow-hidden border bg-white"></div>
                            <div class="space-y-2">
                                <button type="button" @click="openIconModal()" class="btn btn-secondary text-sm w-full">Select Icon</button>
                                <label for="iconUpload" class="btn btn-secondary text-sm w-full cursor-pointer flex items-center justify-center">Upload Icon
                                    <input type="file" id="iconUpload" class="hidden" accept="image/*" @change="handleIconUpload($event)">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label>Color</label>
                        <input x-model="itemForm.color" class="w-full p-2 border rounded coloris" />
                        <div class="mt-3 flex items-center space-x-4">
                            <label class="flex items-center space-x-2"><input type="checkbox" class="h-4 w-4" x-model="itemForm.allow_qr" /> <span class="text-sm">Allow QR</span></label>
                            <label class="flex items-center space-x-2"><input type="checkbox" class="h-4 w-4" x-model="itemForm.allow_share" /> <span class="text-sm">Allow Share</span></label>
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex justify-end space-x-2"><button type="button" @click="closeAllModals()">Cancel</button><button @click="saveItem()" class="bg-orange-500 text-white p-2 rounded">Save</button></div>
            </div>
        </div>
    </template>

    <template x-if="showIconModal">
        <div class="modal">
            <div class="modal-backdrop" @click.self="closeAllModals()"></div>
            <div class="bg-white p-4 rounded shadow w-full max-w-4xl z-30">
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center space-x-3">
                        <h3 class="mr-3">Select an Icon</h3>
                        <input x-model="iconSearchQuery" @input.debounce.300="iconSearch" placeholder="Search icons..." class="p-2 border rounded" />
                        <button @click="resetIconSearch()" type="button" class="p-2">Reset</button>
                    </div>
                    <div>
                        <button type="button" @click="closeAllModals()" class="ghost icon-only"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="icons-grid" x-ref="iconsGrid">
                    <template x-for="p in visibleIcons" :key="p"><div class="p-2 border rounded cursor-pointer hover:bg-gray-50 flex items-center justify-center" @click="selectIcon(p)"><img :src="p" class="h-12 w-12 object-contain" /></div></template>
                    <div x-show="visibleIcons.length===0" class="col-span-full text-center text-gray-500">No icons found.</div>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <div><button x-show="iconPage>0" @click="loadPrevIcons()" class="p-2">Prev</button></div>
                    <div><button x-show="hasMoreIcons" @click="loadMoreIcons()" class="p-2">Load more</button></div>
                    <div><button class="p-2" @click="closeAllModals()">Close</button></div>
                </div>
            </div>
        </div>
    </template>
 <script>
        function adminApp(){
            return {
                data: { categories: [] },
                tab: 'categories', searchQuery:'', filterCategory:'', fabOpen:false,
                showCategoryModal:false, showItemModal:false, showIconModal:false, categoryModalTitle:'', itemModalTitle:'', categoryForm:{id:'',name:'',originalId:''}, itemForm:{id:'',categoryId:'',title:'',value:'',description:'',icon:'',color:'#ff5001',allow_qr:false,allow_share:false},
                icons:[],
                visibleIcons:[],
                iconPage:0,
                iconPageSize:48,
                iconSearchQuery:'',
                hasMoreIcons:false,
                quill:null, fuse:null,
                init(){ this.loadData(); this.theme.apply(); Coloris({el:'.coloris'}); window.adminAppRef = this; this.sortableCategories = null; this.sortableItems = null; },
                showSpinner(text='Working...'){ const el=document.getElementById('globalSpinner'); if(!el) return; const txt=document.getElementById('globalSpinnerText'); if(txt) txt.innerText = text; el.style.display='flex'; },
                hideSpinner(){ const el=document.getElementById('globalSpinner'); if(!el) return; el.style.display='none'; },
                // contrast helper for text over colored backgrounds
                getContrastYIQ(hex){ try{ const c = hex.replace('#',''); const r = parseInt(c.substr(0,2),16); const g = parseInt(c.substr(2,2),16); const b = parseInt(c.substr(4,2),16); const yiq = ((r*299)+(g*587)+(b*114))/1000; return yiq >= 128 ? '#0a0a0a' : '#ffffff'; } catch(e){ return '#ffffff'; } },
                rebuildFuse(){ this.fuse = null; },
                initQuill(content = '') {
                    // Destroy any stale Quill instance and ensure the container is ready
                    if (this.quill) {
                        this.quill = null;
                    }
                    const el = document.getElementById('quillContainer');
                    if (!el) return;
                    
                    // Initialize a new Quill editor
                    this.quill = new Quill(el, { theme: 'snow' });
                    
                    // Set initial content if provided
                    if (content) {
                        this.quill.root.innerHTML = content;
                    }
                },
                runSearch(){ // ensure items tab active when using global search
                    if (this.tab !== 'items') this.tab = 'items';
                    if (!this.fuse) { const list = []; this.data.categories.forEach(c=>c.items?.forEach(i=>list.push({...i, _cat:c.name, _catId:c.id}))); this.fuse = new Fuse(list,{keys:['title','value','description','_cat']}); }
                    if (!this.searchQuery) { this.renderItems(); return; }
                    const results = this.fuse.search(this.searchQuery).map(r=>r.item);
                    this.renderItems(results);
                },
                loadData(){ const self=this; this.showSpinner('Loading...'); $.ajax({url:window.location.href, type:'GET', dataType:'json', data:{action:'getData', csrf_token:'<?= $token ?>'}}).done(res=>{ if(res.success){ self.data = res.data; self.renderCategories(); self.renderItems(); self.rebuildFuse(); } else Swal.fire('Error',res.message||'Load failed'); }).fail(e=>Swal.fire('Error','Connection failed')).always(()=>{ self.hideSpinner(); }); },
                renderCategories(){ 
                    const el = document.getElementById('categoriesList');
                    el.innerHTML='';
                    this.data.categories.forEach(c=>{
                        const div = document.createElement('div');
                        div.className = 'card p-3 flex items-center justify-between';
                        div.setAttribute('data-id', c.id);
                        div.setAttribute('data-name', c.name);
                        div.innerHTML = `<div><span class="text-xl">${c.name}</span><div class="text-xs text-gray-500">ID: ${c.id} • ${ (c.items||[]).length } items</div></div><div class="flex items-center space-x-2"><button class="ghost icon-only hover:bg-green-800" onclick="window.adminAppRef.openAddItem('${c.id}')"><i class="fas fa-plus"></i></button><button class="ghost icon-only" onclick="window.adminAppRef.openEditCategory('${c.id}')"><i class="fas fa-edit"></i></button><button class="ghost icon-only " onclick="window.adminAppRef.deleteCategory('${c.id}')"><i class="fas fa-trash-alt"></i></button></div>`;
                        el.appendChild(div);
                    });
                    // init Sortable for categories
                    try {
                        if (this.sortableCategories) { this.sortableCategories.destroy(); this.sortableCategories = null; }
                        this.sortableCategories = Sortable.create(el, {
                            animation:150,
                            onEnd: (evt) => {
                                const ids = Array.from(el.children).map(n=>n.getAttribute('data-id'));
                                this.showSpinner('Saving category order...');
                                $.post(window.location.href, { action:'saveOrder', type:'categories', order: ids, csrf_token: '<?= $token ?>' })
                                    .done(r=>{ if (r.success) { this.data = r.data; this.renderCategories(); this.renderItems(); this.rebuildFuse(); } else Swal.fire('Error', r.message || 'Save order failed'); })
                                    .always(()=>{ this.hideSpinner(); });
                            }
                        });
                    } catch(e) { console.warn('Sortable init failed', e); }
                },
                renderItems(list=null){
                    const self = this;
                    const grid = document.getElementById('itemsGrid');
                    grid.innerHTML = '';
                    const categories = this.data.categories || [];
                    const filter = this.filterCategory;
                    const itemsToRender = [];
                    categories.forEach(c => { if (filter && c.id !== filter) return; (c.items || []).forEach(i => itemsToRender.push({...i, _catId: c.id, _catName: c.name})); });
                    const final = list || itemsToRender;
                    final.forEach(it => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'card item-card';
                        wrapper.setAttribute('data-id', it.id);
                        wrapper.setAttribute('data-cat', it._catId || '');
                        wrapper.setAttribute('data-icon', it.icon || '');
                        wrapper.setAttribute('data-color', it.color || '');
                        const bg = it.color || '#ff5001';
                        const textColor = this.getContrastYIQ(bg);
                        const iconHtml = it.icon ? `<img src="${it.icon}" class="h-30 w-30 object-contain">` : '<i class="fas fa-info-circle text-white text-2xl"></i>';
                        const qrIcon = it.allow_qr ? '<i class="fas fa-qrcode" title="QR enabled"></i>' : '<i class="fas fa-qrcode opacity-30" title="QR disabled"></i>';
                        const shareIcon = it.allow_share ? '<i class="fas fa-share-alt" title="Share enabled"></i>' : '<i class="fas fa-share-alt opacity-30" title="Share disabled"></i>';
                        wrapper.style.background = bg;
                        wrapper.style.color = textColor;
                        wrapper.style.position = 'relative';
                        let btnBgPrimary = '';
                        let btnBgSecondary = '';
                        let btnBgDanger = '';
                        let btnIconColor = '';
                        if (textColor === '#ffffff') {
                            btnBgPrimary = 'rgba(255,255,255,0.10)'; btnBgSecondary = 'rgba(255,255,255,0.06)'; btnBgDanger = 'rgba(255,255,255,0.04)'; btnIconColor = '#ffffff';
                        } else {
                            btnBgPrimary = 'rgba(0,0,0,0.08)'; btnBgSecondary = 'rgba(0,0,0,0.06)'; btnBgDanger = 'rgba(0,0,0,0.10)'; btnIconColor = '#0a0a0a';
                        }
                        wrapper.innerHTML = `
                            <div class="item-icon-wrap" style="color:${textColor}">
                                <div style="width:110px;height:67%;border-radius:0px 8px 8px 0px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.08); padding:1rem;">${iconHtml}</div>
                            </div>
                            <div class="item-content">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <div>
                                        <div class="item-title syne" style="color:${textColor}">${it.title}</div>
                                        <div style="display:flex;align-items:center;gap:8px"><div class="item-value syne" style="color:${textColor};opacity:0.95">${it.value}</div><div class="meta-icons" style="color:${textColor}">${qrIcon}${shareIcon}</div></div>
                                    </div>
                                </div>
                            </div>
                            <div style="position:absolute;right:10px;top:8px;display:flex;flex-direction:column;gap:15px">
                                <button class="action-btn action-btn-shadow" style="border:1px solid rgba(255,255,255,0.12);background:${btnBgPrimary};color:${btnIconColor};" onclick="window.adminAppRef.openEditItem('${it._catId}','${it.id}')" title="Edit"><i class="fas fa-edit"></i></button>
                                <button class="action-btn action-btn-shadow" style="border:1px solid rgba(255,255,255,0.10);background:${btnBgSecondary};color:${btnIconColor};" onclick="window.adminAppRef.openMoveItem('${it._catId}','${it.id}')" title="Move"><i class="fas fa-arrows-alt"></i></button>
                                <button class="action-btn action-btn-shadow" style="border:1px solid rgba(255,255,255,0.14);background:${btnBgDanger};color:${btnIconColor};" onclick="window.adminAppRef.deleteItem('${it._catId}','${it.id}')" title="Delete"><i class="fas fa-trash-alt"></i></button>
                            </div>`;
                        grid.appendChild(wrapper);
                        wrapper.addEventListener('dblclick', function(){ try{ self.openEditItem(it._catId, it.id); } catch(e){} });
                    });
                    // init Sortable for items when a category filter is active
                    try { if (this.sortableItems) { this.sortableItems.destroy(); this.sortableItems = null; } if (this.filterCategory) { this.sortableItems = Sortable.create(grid, { animation: 150, onEnd: (evt) => { const ids = Array.from(grid.querySelectorAll('[data-id]')).map(n=>n.getAttribute('data-id')); this.showSpinner('Saving item order...'); $.post(window.location.href, { action: 'saveOrder', type: 'items', categoryId: this.filterCategory, order: ids, csrf_token: '<?= $token ?>' }).done(r=>{ if (r.success) { this.data = r.data; this.renderItems(); this.rebuildFuse(); } else Swal.fire('Error', r.message || 'Save order failed'); }).always(()=>{ this.hideSpinner(); }); } }); } } catch(e) { console.warn('Sortable items init failed', e); }
                },
                openCategoryModal(){ this.categoryModalTitle='Add Category'; this.categoryForm={id:'',name:'',originalId:''}; this.showCategoryModal=true; },
                openEditCategory(id){ const c = this.data.categories.find(x=>x.id===id); if(!c) return; this.categoryModalTitle='Edit Category'; this.categoryForm={id:c.id,name:c.name,originalId:c.id}; this.showCategoryModal=true; },
                saveCategory(){ const self=this; const payload = { action:'saveCategory', isEdit: this.categoryForm.originalId?true:false, name:this.categoryForm.name, id:this.categoryForm.id, originalId:this.categoryForm.originalId, csrf_token:'<?= $token ?>' }; this.showSpinner('Saving category...'); $.post(window.location.href,payload).done(res=>{ if(res.success){ self.data = res.data; self.renderCategories(); self.renderItems(); self.rebuildFuse(); self.showCategoryModal=false; Swal.fire('Saved','Category saved','success'); } else Swal.fire('Error',res.message||'Save failed'); }).fail(()=>Swal.fire('Error','Request failed')).always(()=>{ self.hideSpinner(); }); },
                deleteCategory(id){ const self=this; Swal.fire({title:'Delete?',text:'Delete category and items?',showCancelButton:true}).then(r=>{ if(!r.isConfirmed) return; self.showSpinner('Deleting category...'); $.post(window.location.href,{action:'deleteCategory',id:id,csrf_token:'<?= $token ?>'}).done(res=>{ if(res.success){ self.data=res.data; self.renderCategories(); self.renderItems(); Swal.fire('Deleted','Category deleted','success'); } else Swal.fire('Error',res.message||'Failed'); }).always(()=>{ self.hideSpinner(); }); }); },
                openItemModal(){
                    this.itemModalTitle = 'Add Item';
                    const firstCat = this.data.categories[0] ? this.data.categories[0].id : '';
                    this.itemForm = {id: '', categoryId: firstCat, originalCategory: firstCat, title: '', value: '', description: '', icon: '', color: '#ff5001', allow_qr: false, allow_share: false};
                    this.showItemModal = true;
                    this.$nextTick(() => {
                        this.initQuill(''); // Initialize with empty content
                        const preview = document.getElementById('iconPreview');
                        if (preview) preview.innerHTML = this.itemForm.icon ? `<img src="${this.itemForm.icon}" class="h-full w-full object-contain">` : '<i class="fas fa-image text-2xl text-gray-400"></i>';
                        Coloris({el: '.coloris'});
                    });
                },
                openAddItem(catId){ this.openItemModal(); this.itemForm.categoryId = catId; },
                openEditItem(catId, itemId){
                    const c = this.data.categories.find(x => x.id === catId);
                    if (!c) return;
                    const it = c.items.find(x => x.id === itemId);
                    if (!it) return;
                    this.itemModalTitle = 'Edit Item';
                    this.itemForm = {...it, categoryId: catId, originalCategory: catId};
                    this.showItemModal = true;
                    this.$nextTick(() => {
                        this.initQuill(it.description || ''); // Initialize with existing content
                        const preview = document.getElementById('iconPreview');
                        if (preview) preview.innerHTML = it.icon ? `<img src="${it.icon}" class="h-full w-full object-contain">` : '<i class="fas fa-image text-2xl text-gray-400"></i>';
                        Coloris({el: '.coloris'});
                    });
                },
                // called when user changes category in the item modal
                onCategoryChange(newCat){ const prev = this.itemForm.originalCategory || ''; if(!this.itemForm.id) { // adding new item, just update originalCategory
                    this.itemForm.originalCategory = newCat; return; }
                    // when editing an existing item, confirm move intent now or on save
                    if(prev && prev !== newCat){ Swal.fire({ title: 'Category changed', text: 'You changed the category. When you save, you will be prompted to move this item to the new category. Proceed?', icon: 'info', showCancelButton: true }).then(r=>{ if(!r.isConfirmed){ // revert selection
                                this.itemForm.categoryId = prev; } else { /* user ok to continue; keep newCat and wait for save to perform move */ } }); }
                },
                openIconModal(){ const self=this; this.showSpinner('Loading icons...'); $.ajax({url:window.location.href, type:'GET', dataType:'json', data:{action:'getIcons', csrf_token:'<?= $token ?>'}}).done(res=>{ if(res.success){ self.icons = res.icons || []; self.iconPage = 0; self.iconSearchQuery = ''; self.updateVisibleIcons(); self.showIconModal=true; } else Swal.fire('Error', res.message||'Failed to load icons'); }).fail(()=>Swal.fire('Error','Connection failed')).always(()=>{ self.hideSpinner(); }); },
                updateVisibleIcons(){ const q = (this.iconSearchQuery||'').toLowerCase().trim(); const filtered = q ? this.icons.filter(p=>p.toLowerCase().includes(q)) : this.icons.slice(); const start = this.iconPage * this.iconPageSize; const end = start + this.iconPageSize; this.visibleIcons = filtered.slice(start,end); this.hasMoreIcons = end < filtered.length; },
                loadMoreIcons(){ this.iconPage++; this.updateVisibleIcons(); },
                loadPrevIcons(){ if(this.iconPage>0) this.iconPage--; this.updateVisibleIcons(); },
                iconSearch(){ this.iconPage = 0; this.updateVisibleIcons(); },
                resetIconSearch(){ this.iconSearchQuery=''; this.iconPage=0; this.updateVisibleIcons(); },
                selectIcon(path){ this.itemForm.icon = path; // update preview
                    const preview = document.getElementById('iconPreview'); if(preview) preview.innerHTML = `<img src="${path}" class="h-full w-full object-contain">`; this.showIconModal=false; },
                handleIconUpload(ev){ const f = ev?.target?.files?.[0]; if(!f) return; const reader = new FileReader(); reader.onload = e => { const preview = document.getElementById('iconPreview'); if(preview) preview.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-contain">`; }; reader.readAsDataURL(f); // override selected icon path
                    this.itemForm.icon = ''; },
                closeAllModals(){ this.showItemModal=false; this.showCategoryModal=false; this.showIconModal=false; },
                saveItem(){ const self=this; this.itemForm.description = this.quill.root.innerHTML; const fd = new FormData(); fd.append('action','saveItem'); fd.append('id', this.itemForm.id || ''); fd.append('categoryId', this.itemForm.categoryId); fd.append('title', this.itemForm.title); fd.append('description', this.itemForm.description); fd.append('value', this.itemForm.value); fd.append('existingIcon', this.itemForm.icon||''); fd.append('color', this.itemForm.color); fd.append('allowQr', this.itemForm.allow_qr ? '1' : '0'); fd.append('allowShare', this.itemForm.allow_share ? '1' : '0'); fd.append('csrf_token','<?= $token ?>'); // include uploaded file if any
                    const fileEl = document.getElementById('iconUpload'); if (fileEl && fileEl.files && fileEl.files.length) fd.append('icon', fileEl.files[0]); this.showSpinner('Saving item...'); $.ajax({url:window.location.href,type:'POST',data:fd,processData:false,contentType:false}).done(res=>{ if(res.success){ const prevCategory = self.itemForm.originalCategory || null; const newCategory = self.itemForm.categoryId || null; self.data=res.data; self.renderCategories(); self.renderItems(); self.rebuildFuse(); // if category changed and this was an existing item, prompt to move
                                    if(prevCategory && newCategory && prevCategory !== newCategory && self.itemForm.id){ Swal.fire({ title: 'Move item?', text: 'You changed the category. Do you want to move this item to the new category now?', icon: 'question', showCancelButton:true, confirmButtonText:'Yes, move', cancelButtonText:'No, keep here' }).then(choice=>{ if(choice.isConfirmed){ self.showSpinner('Moving item...'); $.post(window.location.href,{ action:'moveItem', fromCategory: prevCategory, toCategory: newCategory, itemId: self.itemForm.id, csrf_token: '<?= $token ?>' }).done(mr=>{ if(mr.success){ self.data=mr.data; self.renderCategories(); self.renderItems(); self.rebuildFuse(); Swal.fire('Moved','Item moved','success'); } else Swal.fire('Error',mr.message||'Move failed'); }).fail(()=>Swal.fire('Error','Request failed')).always(()=>{ self.hideSpinner(); self.showItemModal=false; }); } else { self.showItemModal=false; } });
                                    } else { self.showItemModal=false; Swal.fire('Saved','Item saved','success'); }
                                } else Swal.fire('Error',res.message||'Failed'); }).fail(()=>Swal.fire('Error','Request failed')).always(()=>{ self.hideSpinner(); }); },
                deleteItem(catId, itemId){ const self=this; Swal.fire({title:'Delete item?',showCancelButton:true}).then(r=>{ if(!r.isConfirmed) return; self.showSpinner('Deleting item...'); $.post(window.location.href,{action:'deleteItem',categoryId:catId,id:itemId,csrf_token:'<?= $token ?>'}).done(res=>{ if(res.success){ self.data=res.data; self.renderItems(); self.rebuildFuse(); Swal.fire('Deleted','Item deleted','success'); } else Swal.fire('Error',res.message||'Failed'); }).always(()=>{ self.hideSpinner(); }); }); },
                openMoveItem(catId,itemId){ const self=this; const options = this.data.categories.filter(c=>c.id!==catId).map(c=>`<option value="${c.id}">${c.name}</option>`).join(''); if(!options){ Swal.fire('Move','No other categories to move to'); return; } const srcName = (this.data.categories.find(c=>c.id===catId) || {}).name || 'Current Category'; Swal.fire({ title: `Move from: ${srcName}`, html: `<select id="swalMoveSelect" class="swal2-select">${options}</select>`, showCancelButton:true, preConfirm: ()=>{ const v = document.getElementById('swalMoveSelect').value; return v; } }).then(r=>{ if(!r.isConfirmed) return; const to = r.value; self.showSpinner('Moving item...'); $.post(window.location.href, { action:'moveItem', fromCategory: catId, toCategory: to, itemId: itemId, csrf_token: '<?= $token ?>' }).done(res=>{ if(res.success){ self.data = res.data; self.renderCategories(); self.renderItems(); self.rebuildFuse(); Swal.fire('Moved','Item moved','success'); } else Swal.fire('Error', res.message||'Failed'); }).fail(()=>Swal.fire('Error','Request failed')).always(()=>{ self.hideSpinner(); }); }); },
                openEditCategoryInline(id){},
                theme: {
                    apply(){ const isDark = localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); document.documentElement.classList.toggle('dark', isDark); const sun = document.querySelector('#theme-toggle .light-icon'); const moon = document.querySelector('#theme-toggle .dark-icon'); if(sun) sun.classList.toggle('hidden', !isDark); if(moon) moon.classList.toggle('hidden', isDark); },
                    toggle(){ localStorage.theme = document.documentElement.classList.contains('dark') ? 'light' : 'dark'; this.apply(); }
                }
            };
        }
    
    </script>
    
</body>
</html>