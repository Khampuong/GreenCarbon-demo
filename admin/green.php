<?php
// ตั้งค่า Green: ค่าสัมประสิทธิ์การปล่อยคาร์บอน และหมวดขยะอิเล็กทรอนิกส์ (แต่ละแถวคือ 1 ฟอร์ม แถวสุดท้ายใช้เพิ่มรายการ)
require '../init.php';
need_admin();

if (isset($_POST['del_factor'])) {
    q('DELETE FROM factors WHERE id = ?', [p('id')]);
    go('green.php', 'ลบค่าสัมประสิทธิ์แล้ว');
}
if (isset($_POST['save_factor'])) {
    if (p('name') == '' || p('unit') == '' || (float) p('factor') <= 0) go('green.php', 'กรุณากรอกชื่อ หน่วย และค่ามากกว่า 0', 'danger');
    if (p('id')) q('UPDATE factors SET name = ?, unit = ?, factor = ? WHERE id = ?', [p('name'), p('unit'), p('factor'), p('id')]);
    else q('INSERT INTO factors (name, unit, factor) VALUES (?, ?, ?)', [p('name'), p('unit'), p('factor')]);
    go('green.php', 'บันทึกค่าสัมประสิทธิ์แล้ว');
}
if (isset($_POST['del_category'])) {
    if (val('SELECT 1 FROM recycles WHERE category_id = ?', [p('id')])) go('green.php', 'ลบไม่ได้: มีคำขอรีไซเคิลใช้หมวดนี้อยู่', 'danger');
    q('DELETE FROM categories WHERE id = ?', [p('id')]);
    go('green.php', 'ลบหมวดแล้ว');
}
if (isset($_POST['save_category'])) {
    if (p('name') == '' || (float) p('co2_per_kg') <= 0) go('green.php', 'กรุณากรอกชื่อหมวด และค่ามากกว่า 0', 'danger');
    if (p('id')) q('UPDATE categories SET name = ?, co2_per_kg = ? WHERE id = ?', [p('name'), p('co2_per_kg'), p('id')]);
    else q('INSERT INTO categories (name, co2_per_kg) VALUES (?, ?)', [p('name'), p('co2_per_kg')]);
    go('green.php', 'บันทึกหมวดแล้ว');
}

$factors = all('SELECT * FROM factors ORDER BY id');
$factors[] = ['id' => '', 'name' => '', 'unit' => '', 'factor' => ''];
$cats = all('SELECT * FROM categories ORDER BY id');
$cats[] = ['id' => '', 'name' => '', 'co2_per_kg' => ''];

head('ตั้งค่า Green');
?>
<div class="card card-body mb-4">
  <h5>ค่าสัมประสิทธิ์การปล่อยคาร์บอน (ใช้ในแบบคำนวณฟุตพริ้นท์)</h5>
  <?php foreach ($factors as $f): ?>
    <form method="post" class="row g-2 mb-2">
      <input type="hidden" name="id" value="<?= $f['id'] ?>">
      <div class="col-md-5"><input class="form-control" name="name" value="<?= e($f['name']) ?>" placeholder="ชื่อรายการ" required></div>
      <div class="col-md-2"><input class="form-control" name="unit" value="<?= e($f['unit']) ?>" placeholder="หน่วย" required></div>
      <div class="col-md-2"><input class="form-control" name="factor" type="number" step="0.001" min="0.001" value="<?= $f['factor'] ?>" placeholder="kgCO₂e ต่อหน่วย" required></div>
      <div class="col-md-3">
        <button class="btn btn-success" name="save_factor"><?= $f['id'] ? 'บันทึก' : 'เพิ่ม' ?></button>
        <?php if ($f['id']): ?><button class="btn btn-outline-danger" name="del_factor" onclick="return confirm('ลบรายการนี้?')">ลบ</button><?php endif; ?>
      </div>
    </form>
  <?php endforeach; ?>
</div>

<div class="card card-body">
  <h5>หมวดขยะอิเล็กทรอนิกส์ (kgCO₂e ที่ลดได้เมื่อรีไซเคิล 1 กก.)</h5>
  <?php foreach ($cats as $c): ?>
    <form method="post" class="row g-2 mb-2">
      <input type="hidden" name="id" value="<?= $c['id'] ?>">
      <div class="col-md-7"><input class="form-control" name="name" value="<?= e($c['name']) ?>" placeholder="ชื่อหมวด" required></div>
      <div class="col-md-2"><input class="form-control" name="co2_per_kg" type="number" step="0.01" min="0.01" value="<?= $c['co2_per_kg'] ?>" placeholder="kgCO₂e ต่อ กก." required></div>
      <div class="col-md-3">
        <button class="btn btn-success" name="save_category"><?= $c['id'] ? 'บันทึก' : 'เพิ่ม' ?></button>
        <?php if ($c['id']): ?><button class="btn btn-outline-danger" name="del_category" onclick="return confirm('ลบหมวดนี้?')">ลบ</button><?php endif; ?>
      </div>
    </form>
  <?php endforeach; ?>
</div>
<?php foot() ?>
