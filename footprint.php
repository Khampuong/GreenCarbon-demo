<?php
// คำนวณคาร์บอนฟุตพริ้นท์รายปี = ผลรวมของ (ปริมาณต่อเดือน x ค่าสัมประสิทธิ์ x 12)
require 'init.php';
$u = need_login();
$year = date('Y');
$factors = all('SELECT * FROM factors ORDER BY id');

if ($_POST) {
    $total = 0;
    foreach ($factors as $f) $total += max(0, (float) ($_POST['amount'][$f['id']] ?? 0)) * $f['factor'] * 12;
    if ($total <= 0) go('footprint.php', 'กรุณากรอกข้อมูลอย่างน้อย 1 รายการ', 'danger');
    if (val('SELECT 1 FROM footprints WHERE user_id = ? AND year = ?', [$u['id'], $year])) {
        q('UPDATE footprints SET total_kg = ? WHERE user_id = ? AND year = ?', [$total, $u['id'], $year]);
        go('footprint.php', 'บันทึกผลคำนวณใหม่แล้ว');
    }
    q('INSERT INTO footprints (user_id, year, total_kg) VALUES (?, ?, ?)', [$u['id'], $year, $total]);
    q('UPDATE users SET points = points + ? WHERE id = ?', [POINT_FOOTPRINT, $u['id']]);
    go('footprint.php', 'บันทึกแล้ว รับ ' . POINT_FOOTPRINT . ' แต้มจากการคำนวณครั้งแรกของปี');
}

$fp = one('SELECT * FROM footprints WHERE user_id = ? AND year = ?', [$u['id'], $year]);
$saved = val("SELECT COALESCE(SUM(co2_kg), 0) FROM recycles WHERE user_id = ? AND status = 'done' AND YEAR(created_at) = ?", [$u['id'], $year]);
$history = all('SELECT year + 543 AS y, total_kg FROM footprints WHERE user_id = ? ORDER BY year', [$u['id']]);

head('คาร์บอนฟุตพริ้นท์ ปี ' . ($year + 543), !$fp);
?>
<?php if ($fp): $left = max(0, $fp['total_kg'] - $saved); $pct = min(100, round($saved / $fp['total_kg'] * 100, 1)); ?>
  <div class="hero text-center mb-4">
    <p class="lead mb-0">คาร์บอนฟุตพริ้นท์ของคุณ ปี <?= $year + 543 ?></p>
    <div class="big"><?= number_format($fp['total_kg']) ?> kgCO₂e</div>
    <p class="lead">เส้นทางสู่ Net Zero: ชดเชยแล้ว <?= $pct ?>%</p>
    <div class="progress"><div class="progress-bar bg-warning" style="width:<?= $pct ?>%"></div></div>
  </div>
  <div class="row justify-content-center">
    <?php box('♻️ ลดได้จากการรีไซเคิล', $saved . ' kgCO₂e') ?>
    <?php box('📉 คงเหลือสุทธิ', number_format($left) . ' kgCO₂e') ?>
    <?php box('🌳 ต้องปลูกต้นไม้ชดเชย', ceil($left / TREE_KG) . ' ต้น') ?>
  </div>
  <div class="alert alert-success">💡 คำแนะนำ: ตั้งแอร์ 26 องศา ถอดปลั๊กเมื่อไม่ใช้ และ<a href="recycle.php">ส่งขยะอิเล็กทรอนิกส์ไปรีไซเคิล</a>เพื่อเพิ่มแถบ Net Zero</div>
<?php endif; ?>

<div class="row">
  <?php if ($fp): ?>
    <div class="col-lg-6"><?php chart('คาร์บอนฟุตพริ้นท์รายปี (kgCO₂e)', 'bar', array_column($history, 'y'), array_column($history, 'total_kg')) ?></div>
  <?php endif; ?>
  <div class="col-lg">
    <form method="post" class="card card-body mb-3">
      <h5><?= $fp ? '🧮 คำนวณใหม่' : '🧮 กรอกปริมาณที่ใช้ต่อเดือน' ?></h5>
      <?php foreach ($factors as $f): ?>
        <?php input(e($f['name']) . ' (' . e($f['unit']) . ' ต่อเดือน)', 'amount[' . $f['id'] . ']', 'number', '', 'min="0" step="0.01"') ?>
      <?php endforeach; ?>
      <button class="btn btn-success mt-3">คำนวณและบันทึก</button>
    </form>
  </div>
</div>
<?php foot() ?>
