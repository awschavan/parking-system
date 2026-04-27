<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/parking.php';

requireLogin();

$user     = getCurrentUser();
$blocks   = ['A','B','D','E','F','G'];
$allSlots = [];
foreach ($blocks as $b) {
    $allSlots[$b] = getSlotsByBlock($b);
}

$activeCount = getUserActiveBookingCount($_SESSION['user_id']);
$slotsLeft   = 5 - $activeCount;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slotNumber    = trim($_POST['selected_slot']    ?? '');
    $blockName     = trim($_POST['selected_block']   ?? '');
    $duration      = trim($_POST['duration']         ?? '');
    $vehicleNumber = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $bookingDate   = trim($_POST['booking_date']     ?? '');
    $startTime     = trim($_POST['start_time']       ?? '');
    $endTime       = trim($_POST['end_time']         ?? '');

    if ($activeCount >= 5) {
        $error = 'Maximum 5 active bookings per day. Please cancel or wait for a booking to expire.';
    } elseif (empty($slotNumber) || empty($blockName)) {
        $error = 'Please select a parking slot on the grid.';
    } elseif (!in_array($duration, ['1 hour','1 day'])) {
        $error = 'Please select a valid duration.';
    } elseif (empty($vehicleNumber) || strlen($vehicleNumber) < 4) {
        $error = 'Please enter a valid vehicle number (min 4 characters).';
    } elseif (empty($bookingDate)) {
        $error = 'Please select a booking date.';
    } elseif (empty($startTime)) {
        $error = 'Please select a start time.';
    } else {
        unset($_SESSION['razorpay_order_id'], $_SESSION['razorpay_booking_id'], $_SESSION['razorpay_amount']);
        $_SESSION['pending_booking'] = [
            'slot_number'    => $slotNumber,
            'block_name'     => $blockName,
            'duration'       => $duration,
            'vehicle_number' => $vehicleNumber,
            'user_id'        => $_SESSION['user_id'],
            'booking_date'   => $bookingDate,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
        ];
        header('Location: ' . APP_URL . '/payment.php');
        exit;
    }
}

$today = date('Y-m-d');
$pageTitle = 'Book a Slot';
include __DIR__ . '/includes/header.php';
?>

<style>
.book-wrap { min-height: calc(100vh - 140px); }
.block-pills { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.5rem; }
.bpill {
  padding:.45rem 1rem;
  border-radius:999px;
  border:1.5px solid rgba(255,255,255,.1);
  background:rgba(255,255,255,.04);
  color:var(--text-muted);
  font-family:'Syne',sans-serif;
  font-weight:700;
  font-size:.85rem;
  cursor:pointer;
  transition:all .2s;
  display:inline-flex;
  align-items:center;
  gap:.35rem;
}
.bpill:hover { border-color:var(--brand-accent); color:var(--brand-accent); }
.bpill.active { background:var(--brand-accent); border-color:var(--brand-accent); color:#fff; }
.bpill .bc { font-size:.68rem; background:rgba(255,255,255,.25); padding:.05rem .4rem; border-radius:999px; }

.sgrid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: .55rem;
  margin-top: .75rem;
}
.sbtn {
  aspect-ratio: 1;
  border-radius: 10px;
  border: 2px solid transparent;
  font-family:'Syne',sans-serif;
  font-weight:800;
  font-size:.9rem;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-direction:column;
  gap:2px;
  transition:all .15s;
  background:none;
  padding:0;
}
.sbtn.available {
  background:rgba(34,197,94,.12);
  border-color:rgba(34,197,94,.35);
  color:#22c55e;
}
.sbtn.available:hover {
  background:rgba(34,197,94,.22);
  border-color:#22c55e;
  transform:scale(1.07);
}
.sbtn.booked {
  background:rgba(239,68,68,.07);
  border-color:rgba(239,68,68,.18);
  color:rgba(239,68,68,.45);
  cursor:not-allowed;
}
.sbtn.selected {
  background:rgba(234,179,8,.18);
  border-color:#eab308;
  color:#eab308;
  transform:scale(1.09);
  box-shadow:0 0 18px rgba(234,179,8,.28);
}
.sbtn .si { font-size:.6rem; opacity:.65; }

.rpanel {
  background:var(--card-bg);
  border:1px solid var(--border);
  border-radius:14px;
  padding:1.4rem;
  position:sticky;
  top:76px;
}
.flabel {
  font-size:.74rem;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.5px;
  color:var(--text-muted);
  margin-bottom:.35rem;
  display:block;
}
.slot-box {
  background:rgba(234,179,8,.07);
  border:1.5px solid rgba(234,179,8,.28);
  border-radius:9px;
  padding:.65rem 1rem;
  display:flex;
  align-items:center;
  gap:.65rem;
}
.slot-box .sn { font-family:'Syne',sans-serif; font-size:1.35rem; font-weight:800; color:#eab308; }
.placeholder-box {
  background:rgba(255,255,255,.025);
  border:1.5px dashed rgba(255,255,255,.09);
  border-radius:9px;
  padding:.65rem;
  text-align:center;
  font-size:.82rem;
  color:var(--text-muted);
}
.dur-wrap { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
.dur-opt {
  border:2px solid rgba(255,255,255,.1);
  border-radius:9px;
  padding:.65rem;
  text-align:center;
  cursor:pointer;
  transition:all .2s;
}
.dur-opt.on { border-color:var(--brand-accent); background:rgba(249,115,22,.08); }
.dur-opt .dt { font-family:'Syne',sans-serif; font-weight:700; font-size:.9rem; }
.dur-opt .dp { font-weight:700; color:var(--text-muted); font-size:.9rem; }
.dur-opt.on .dp { color:var(--brand-accent); }
.price-box {
  background:rgba(249,115,22,.06);
  border:1px solid rgba(249,115,22,.14);
  border-radius:9px;
  padding:.9rem 1rem;
}
.ptag { font-family:'Syne',sans-serif; font-size:1.75rem; font-weight:800; color:var(--brand-accent); }
@media(max-width:576px) { .sgrid{grid-template-columns:repeat(4,1fr);gap:.4rem;} }
</style>

<div class="book-wrap">
<div class="container py-4 pb-5">

<?php if ($error): ?>
<div class="alert-custom alert-danger-custom mb-4 auto-dismiss"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- LEFT -->
<div class="col-lg-8">
<div class="card-dark">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0">Select Parking Slot</h5>
      <small class="text-muted">Choose a block then click any green slot</small>
    </div>
    <div class="d-flex gap-3 align-items-center flex-wrap">
      <div class="legend-item"><div class="legend-dot" style="background:#22c55e"></div>Available</div>
      <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div>Booked</div>
      <div class="legend-item"><div class="legend-dot" style="background:#eab308"></div>Selected</div>
    </div>
  </div>

  <!-- Block Pills -->
  <div class="block-pills">
    <?php foreach ($blocks as $i => $b):
      $av = count(array_filter($allSlots[$b], fn($s) => $s['status'] === 'available'));
    ?>
    <button class="bpill <?= $i===0?'active':'' ?>"
            id="pill-<?= $b ?>"
            onclick="showBlock('<?= $b ?>')">
      Block <?= $b ?> <span class="bc"><?= $av ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Slot Grids — all rendered, show/hide with JS -->
  <?php foreach ($blocks as $i => $b): ?>
  <div id="grid-<?= $b ?>" style="display:<?= $i===0?'block':'none' ?>">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem">
      <div style="width:7px;height:7px;background:var(--brand-accent);border-radius:2px"></div>
      <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:.88rem">Block <?= $b ?> — <?= count(array_filter($allSlots[$b], fn($s) => $s['status']==='available')) ?> available</span>
    </div>
    <div class="sgrid">
      <?php foreach ($allSlots[$b] as $sl):
        $st  = $sl['status'];
        $dis = $st !== 'available' ? 'disabled' : '';
        $tip = $sl['slot_number'].' — '.ucfirst($st);
        if ($st==='booked' && !empty($sl['booked_vehicle'])) $tip.=' ('.$sl['booked_vehicle'].')';
      ?>
      <button class="sbtn <?= $st ?>"
              <?= $dis ?>
              onclick="pickSlot(this,'<?= $sl['slot_number'] ?>','<?= $b ?>')"
              title="<?= htmlspecialchars($tip) ?>">
        <span><?= htmlspecialchars($sl['slot_number']) ?></span>
        <span class="si"><?= $st==='available'?'🅿':($st==='booked'?'🚗':'🔧') ?></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

</div>
</div><!-- /col -->

<!-- RIGHT -->
<div class="col-lg-4">
<div class="rpanel">

  <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1.1rem">
    <i class="bi bi-clipboard-check me-2 text-accent"></i>Booking Details
  </h5>

  <!-- Limit -->
  <div style="background:rgba(249,115,22,.07);border:1px solid rgba(249,115,22,.18);border-radius:8px;padding:.55rem .85rem;font-size:.8rem;margin-bottom:1rem">
    <i class="bi bi-speedometer2 me-1 text-accent"></i>
    <strong><?= $activeCount ?>/5</strong> daily bookings used — <span class="text-muted"><?= $slotsLeft ?> left today</span>
  </div>

  <form method="POST" id="bkForm">
    <input type="hidden" name="selected_slot"  id="hslot">
    <input type="hidden" name="selected_block" id="hblock" value="A">
    <input type="hidden" name="duration"       id="hdur"   value="1 hour">
    <input type="hidden" name="end_time"       id="hend">

    <!-- Selected slot display -->
    <div style="margin-bottom:.9rem">
      <label class="flabel">Selected Slot</label>
      <div id="slotBox" style="display:none" class="slot-box">
        <div class="sn" id="slotName"></div>
        <div style="font-size:.78rem;color:var(--text-muted)">Block <span id="slotBlock"></span></div>
      </div>
      <div id="slotPH" class="placeholder-box">
        <i class="bi bi-hand-index me-1"></i>Click a green slot on the left
      </div>
    </div>

    <!-- Vehicle -->
    <div style="margin-bottom:.9rem">
      <label class="flabel">Vehicle Number *</label>
      <input type="text" name="vehicle_number" class="form-control vehicle-input"
             placeholder="e.g. MH12AB1234"
             value="<?= htmlspecialchars(strtoupper($_POST['vehicle_number'] ?? $user['vehicle_number'])) ?>"
             required>
    </div>

    <!-- Date -->
    <div style="margin-bottom:.9rem">
      <label class="flabel">Booking Date *</label>
      <input type="date" name="booking_date" id="bdate" class="form-control"
             min="<?= $today ?>" value="<?= $today ?>" required>
    </div>

    <!-- Start Time -->
    <div style="margin-bottom:.9rem">
      <label class="flabel">Start Time *</label>
      <input type="time" name="start_time" id="stime" class="form-control"
             value="<?= date('H:i') ?>" required>
    </div>

    <!-- Duration -->
    <div style="margin-bottom:.9rem">
      <label class="flabel">Duration *</label>
      <div class="dur-wrap">
        <div class="dur-opt on" id="d1h" onclick="setDur('1 hour',<?= PRICE_HOURLY ?>)">
          <div class="dt">1 Hour</div>
          <div class="dp">₹<?= PRICE_HOURLY ?></div>
        </div>
        <div class="dur-opt" id="d1d" onclick="setDur('1 day',<?= PRICE_DAILY ?>)">
          <div class="dt">1 Day</div>
          <div class="dp">₹<?= PRICE_DAILY ?></div>
        </div>
      </div>
    </div>

    <!-- End Time -->
    <div style="margin-bottom:.9rem">
      <label class="flabel">End Time (auto)</label>
      <input type="text" id="endDisp" class="form-control" readonly
             style="background:rgba(255,255,255,.03);cursor:default">
    </div>

    <!-- Price -->
    <div class="price-box" style="margin-bottom:1.1rem">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted">Total</span>
        <span class="ptag" id="ptag">₹<?= PRICE_HOURLY ?></span>
      </div>
      <hr style="border-color:rgba(255,255,255,.07);margin:.6rem 0">
      <small class="text-muted"><i class="bi bi-shield-lock me-1"></i>UPI · Google Pay · PhonePe · Cards</small>
    </div>

    <button type="submit" class="btn-primary-custom w-100"
            style="font-size:1rem;padding:.8rem"
            <?= $activeCount>=5?'disabled':'' ?>>
      <i class="bi bi-credit-card me-2"></i>Proceed to Payment
    </button>
  </form>

</div>
</div><!-- /col -->

</div><!-- /row -->
</div>
</div>

<script>
var curDur   = '1 hour';
var curBlock = 'A';

// Show block grid
function showBlock(b) {
  // Hide all grids
  ['A','B','D','E','F','G'].forEach(function(x) {
    var g = document.getElementById('grid-' + x);
    var p = document.getElementById('pill-' + x);
    if (g) g.style.display = 'none';
    if (p) p.classList.remove('active');
  });
  // Show selected
  var tgt = document.getElementById('grid-' + b);
  var pill = document.getElementById('pill-' + b);
  if (tgt) tgt.style.display = 'block';
  if (pill) pill.classList.add('active');

  curBlock = b;
  document.getElementById('hblock').value = b;

  // Reset slot selection
  document.querySelectorAll('.sbtn.selected').forEach(function(el) {
    el.classList.remove('selected');
    el.classList.add('available');
  });
  document.getElementById('hslot').value = '';
  document.getElementById('slotBox').style.display = 'none';
  document.getElementById('slotPH').style.display = 'block';
}

// Pick a slot
function pickSlot(btn, slot, block) {
  if (btn.disabled) return;
  // Deselect all
  document.querySelectorAll('.sbtn.selected').forEach(function(el) {
    el.classList.remove('selected');
    el.classList.add('available');
  });
  btn.classList.remove('available');
  btn.classList.add('selected');

  document.getElementById('hslot').value  = slot;
  document.getElementById('hblock').value = block;
  document.getElementById('slotName').textContent  = slot;
  document.getElementById('slotBlock').textContent = block;
  document.getElementById('slotBox').style.display = 'flex';
  document.getElementById('slotPH').style.display  = 'none';
}

// Set duration
function setDur(d, p) {
  curDur = d;
  document.getElementById('hdur').value = d;
  document.getElementById('ptag').textContent = '₹' + p;
  document.getElementById('d1h').classList.toggle('on', d === '1 hour');
  document.getElementById('d1d').classList.toggle('on', d === '1 day');
  document.getElementById('d1h').querySelector('.dp').style.color = d==='1 hour' ? 'var(--brand-accent)' : 'var(--text-muted)';
  document.getElementById('d1d').querySelector('.dp').style.color = d==='1 day'  ? 'var(--brand-accent)' : 'var(--text-muted)';
  calcEnd();
}

// Calculate end time
function calcEnd() {
  var d = document.getElementById('bdate').value;
  var t = document.getElementById('stime').value;
  if (!d || !t) return;
  var dt = new Date(d + 'T' + t);
  if (curDur === '1 hour') dt.setHours(dt.getHours() + 1);
  else dt.setDate(dt.getDate() + 1);
  var hh = String(dt.getHours()).padStart(2,'0');
  var mm = String(dt.getMinutes()).padStart(2,'0');
  document.getElementById('endDisp').value = hh + ':' + mm;
  document.getElementById('hend').value    = hh + ':' + mm;
}

document.getElementById('stime').addEventListener('change', calcEnd);
document.getElementById('bdate').addEventListener('change', calcEnd);

// Form validation
document.getElementById('bkForm').addEventListener('submit', function(e) {
  if (!document.getElementById('hslot').value) {
    e.preventDefault();
    alert('Please click a green slot to select it first.');
    return;
  }
  var v = document.querySelector('[name="vehicle_number"]').value.trim();
  if (v.length < 4) {
    e.preventDefault();
    alert('Please enter a valid vehicle number (min 4 characters).');
  }
});

// Init
calcEnd();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>