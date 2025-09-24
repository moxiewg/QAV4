/**
 * QA InfoWallet - Enhanced NFC and Sharing Manager
 * Provides advanced NFC writing/reading and comprehensive sharing options
 * Uses only native Web NFC API - no external dependencies
 */

class EnhancedNFCManager {
  constructor() {
    this.isNFCSupported = "NDEFReader" in window;
    this.isWriting = false; // To track if a write operation is in progress
    this.ndef = null; // NDEFReader instance

    if (!this.isNFCSupported) {
      console.warn("WebNFC is not supported by this browser.");
      Swal.fire({
        icon: 'warning',
        title: 'NFC Not Supported',
        text: 'Your browser or device does not support WebNFC. Sharing via NFC will not be available.',
        timer: 5000,
        timerProgressBar: true
      });
    } else {
      console.log("WebNFC is supported by this browser.");
      // Optionally, provide feedback that NFC is ready if needed
    }
  }

  /**
   * Initializes NFC button listeners.
   * @param {string} selector - CSS selector for NFC buttons.
   */
  initNFCButtons(selector = '.nfc-share-button, .nfc-action-button') {
    const nfcButtons = document.querySelectorAll(selector);

    if (!this.isNFCSupported) {
      nfcButtons.forEach(button => {
        button.disabled = true;
        button.title = 'NFC not supported on this device/browser';
      });
      return;
    }

    nfcButtons.forEach(button => {
      button.addEventListener('click', (event) => this.handleNFCButtonClick(event));
    });
  }

  /**
   * Handles the click event for NFC sharing buttons.
   * @param {Event} event - The click event.
   */
  async handleNFCButtonClick(event) {
    if (!this.isNFCSupported || this.isWriting) return;

    const originalValue = event.currentTarget.dataset.value;
    const nameToShare = event.currentTarget.dataset.name;
    const lowerCaseName = nameToShare.toLowerCase();

    if (!originalValue) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No value found to share for this item.'
      });
      return;
    }

    // --- Prepare the data payload based on the item type ---
    let payloadValue = originalValue;
    let recordType = "text";

    if (lowerCaseName.includes('phone number')) {
      const cleanedPhoneNumber = originalValue.replace(/[\s\-\(\)]/g, '');
      payloadValue = `tel:${cleanedPhoneNumber}`;
      recordType = "text";
      console.log("NFC Prep: Formatting as tel:", payloadValue);
    } else if (lowerCaseName.includes('email address')) {
      payloadValue = `mailto:${originalValue.trim()}`;
      recordType = "text";
      console.log("NFC Prep: Formatting as mailto:", payloadValue);
    } else if (originalValue.toLowerCase().startsWith('http://') || originalValue.toLowerCase().startsWith('https://')) {
      recordType = "text"; // Using text for URLs is generally well-supported
      payloadValue = originalValue;
      console.log("NFC Prep: Formatting as URL (using text record):", payloadValue);
    } else {
      recordType = "text";
      payloadValue = originalValue;
      console.log("NFC Prep: Formatting as plain text:", payloadValue);
    }
    // --- End data preparation ---

    Swal.fire({
      title: `Share "${nameToShare}"?`,
      html: `Ready to share via NFC.<br>Value: <code class="text-sm bg-gray-100 p-1 rounded break-all">${originalValue}</code><br><br>Tap the button below, then touch your device to an NFC tag or another NFC-enabled device.`,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: '<i class="fas fa-wifi mr-2"></i>Start Sharing',
      cancelButtonText: 'Cancel',
      allowOutsideClick: false,
    }).then(async (result) => {
      if (result.isConfirmed) {
        this.isWriting = true;
        const writeSwal = Swal.fire({
          title: 'Sharing...',
          html: `Attempting to write "${nameToShare}" via NFC.<br>Keep device near the target.`,
          icon: 'info',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => { Swal.showLoading(); }
        });

        try {
          this.ndef = new NDEFReader();
          await this.ndef.write({
            records: [{ recordType: recordType, data: payloadValue }]
          });

          console.log(`NFC: Successfully wrote value: ${payloadValue}`);
          writeSwal.close();
          Swal.fire({
            icon: 'success',
            title: 'Shared Successfully!',
            text: `"${nameToShare}" data sent via NFC.`,
            timer: 3000,
            timerProgressBar: true
          });

        } catch (error) {
          console.error('NFC write error:', error);
          writeSwal.close();
          let errorMessage = 'Could not share via NFC. Please try again.';
          if (error.name === 'NotAllowedError') {
            errorMessage = 'NFC permission denied or user cancelled the prompt.';
          } else if (error.name === 'InvalidStateError' || error.name === 'NotFoundError') {
            errorMessage = 'NFC operation aborted, device moved away, or tag lost/not supported.';
          } else if (error.name === 'NetworkError') {
            errorMessage = 'NFC communication error. Ensure devices are close and stable.';
          } else if (error.name === 'AbortError') {
            errorMessage = 'NFC operation was cancelled.';
          } else {
            errorMessage = `NFC Error: ${error.name} - ${error.message}`;
          }

          Swal.fire({
            icon: 'error',
            title: 'NFC Sharing Failed',
            text: errorMessage
          });
        } finally {
          this.isWriting = false;
        }
      } else if (result.dismiss === Swal.DismissReason.cancel) {
        console.log('NFC share cancelled by user.');
        Swal.fire({
          icon: 'info',
          title: 'Cancelled',
          text: 'NFC sharing was cancelled.',
          timer: 1500,
          showConfirmButton: false
        });
      }
    });
  }
}