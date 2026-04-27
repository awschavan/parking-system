/* ============================================================
   ParkEase — app.js
   ============================================================ */

// Auto-dismiss alerts after 4 seconds
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.auto-dismiss').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // Vehicle number — uppercase as user types
  document.querySelectorAll('.vehicle-input').forEach(el => {
    el.addEventListener('input', () => { el.value = el.value.toUpperCase(); });
  });

  // Mobile — digits only
  document.querySelectorAll('.mobile-input').forEach(el => {
    el.addEventListener('input', () => { el.value = el.value.replace(/\D/g, '').slice(0, 10); });
  });
});

// ---- Slot Selection ----
let selectedSlot = null;

function selectSlot(btn, slotNumber) {
  if (btn.classList.contains('booked') || btn.classList.contains('maintenance')) return;

  // Deselect previous
  document.querySelectorAll('.slot-btn.selected').forEach(b => {
    b.classList.remove('selected');
    b.classList.add('available');
  });

  if (selectedSlot === slotNumber) {
    // Toggle off
    selectedSlot = null;
    document.getElementById('selected_slot').value = '';
    document.getElementById('slot-confirm-area').style.display = 'none';
    return;
  }

  btn.classList.remove('available');
  btn.classList.add('selected');
  selectedSlot = slotNumber;
  document.getElementById('selected_slot').value = slotNumber;

  const confirmArea = document.getElementById('slot-confirm-area');
  if (confirmArea) {
    confirmArea.style.display = 'block';
    confirmArea.querySelector('.selected-slot-label').textContent = slotNumber;
    confirmArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

// ---- Block Switching ----
function switchBlock(blockName) {
  document.querySelectorAll('.block-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.block-section').forEach(s => s.style.display = 'none');

  document.querySelector('.block-tab[data-block="' + blockName + '"]').classList.add('active');
  const section = document.getElementById('block-' + blockName);
  if (section) section.style.display = 'block';

  // Reset selection when switching blocks
  selectedSlot = null;
  const si = document.getElementById('selected_slot');
  if (si) si.value = '';
  const ca = document.getElementById('slot-confirm-area');
  if (ca) ca.style.display = 'none';
  document.getElementById('selected_block').value = blockName;
}

// ---- Duration / Price Update ----
function updatePrice(select) {
  const prices = {};
  Array.from(select.options).forEach(opt => {
    const price = opt.getAttribute('data-price');
    if (price) prices[opt.value] = price;
  });
  const el = document.getElementById('price-display');
  if (el && prices[select.value]) el.textContent = '₹' + prices[select.value];
}

// ---- Confirm cancel ----
function confirmCancel(bookingId) {
  if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
    window.location.href = 'cancel_booking.php?id=' + bookingId;
  }
}

// ---- Print receipt ----
function printReceipt() {
  window.print();
}
