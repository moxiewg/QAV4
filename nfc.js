document.addEventListener('DOMContentLoaded', () => {
  const nfcButtons = document.querySelectorAll('.nfc-action-button');
  if (!('NDEFReader' in window)) {
    nfcButtons.forEach(btn => btn.disabled = true);
    return;
  }
  nfcButtons.forEach(button => {
    button.addEventListener('click', async (event) => {
      const card = event.currentTarget.closest('.item-card');
      const value = card?.dataset.copyValue || button.dataset.value;
      const name = card?.querySelector('.card-name')?.textContent || button.dataset.name || 'Card';
      if (!value) return alert('No value to share via NFC.');
      try {
        const ndef = new NDEFReader();
        await ndef.write({ records: [{ recordType: 'text', data: value }] });
        alert(`Shared "${name}" via NFC!`);
      } catch (e) {
        alert('NFC sharing failed: ' + e.message);
      }
    });
  });
});