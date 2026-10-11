<?php
// แจ้งรีไซเคิลขยะอิเล็กทรอนิกส์ แนบรูปสินค้า และติดตามสถานะ
require 'init.php';
$u = need_login();
$cats = all('SELECT * FROM categories ORDER BY id');

if (isset($_POST['cancel'])) {
    q("DELETE FROM recycles WHERE id = ? AND user_id = ? AND status = 'pending'", [p('id'), $u['id']]);
    go('recycle.php', 'ยกเลิกคำขอแล้ว', 'warning');
}
if ($_POST) {
    if (mb_strlen(p('item_name')) < 3 || (float) p('weight_kg') <= 0 || !val('SELECT 1 FROM categories WHERE id = ?', [p('category_id')])) {
        go('recycle.php', 'กรุณาเลือกหมวด กรอกรายการอย่างน้อย 3 ตัวอักษร และน้ำหนักมากกว่า 0', 'danger');
    }
    $image = upload('image');
    if (!$image && bad_upload('image')) go('recycle.php', bad_upload('image'), 'danger');
    q('INSERT INTO recycles (user_id, category_id, item_name, weight_kg, image) VALUES (?, ?, ?, ?, ?)', [$u['id'], p('category_id'), p('item_name'), (float) p('weight_kg'), $image]);
    notify(null, 'คำขอรีไซเคิลใหม่', $u['name'] . ': ' . p('item_name'), 'admin/recycles.php?status=pending');
    go('recycle.php', 'ส่งคำขอแล้ว เมื่อผู้ดูแลตรวจรับ คุณจะได้ ' . POINT_PER_KG . ' แต้มต่อ 1 กก.');
}

$sort = g('sort') == 'weight' ? 'r.weight_kg DESC' : 'r.id DESC';
$rows = all("SELECT r.*, c.name AS category FROM recycles r JOIN categories c ON c.id = r.category_id
    WHERE r.user_id = ? AND r.item_name LIKE ? ORDER BY $sort", [$u['id'], like()]);

head('รีไซเคิลขยะอิเล็กทรอนิกส์');
?>
<form method="post" enctype="multipart/form-data" class="card card-body mb-4">
  <h5>♻️ แจ้งรีไซเคิล</h5>
  <label class="form-label mt-2">หมวดอุปกรณ์</label>
  <select class="form-select" name="category_id" required>
    <option value="">เลือกหมวด</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (ลด <?= $c['co2_per_kg'] ?> kgCO₂e ต่อ กก.)</option>
    <?php endforeach; ?>
  </select>
  <?php input('รายการ', 'item_name', 'text', '', 'required minlength="3"') ?>
  <?php input('น้ำหนักโดยประมาณ (กก.)', 'weight_kg', 'number', '', 'required min="0.01" step="0.01"') ?>
  <?php input('รูปสินค้าที่จะรีไซเคิล (ไม่บังคับ JPG PNG GIF ไม่เกิน 2MB)', 'image', 'file', '', 'accept="image/*"') ?>
  <button class="btn btn-success mt-3">ส่งคำขอ</button>
</form>

<h5>คำขอของฉัน</h5>
<?php search('<select class="form-select w-auto" name="sort"><option value="">ใหม่ล่าสุด</option><option value="weight"' . (g('sort') == 'weight' ? ' selected' : '') . '>น้ำหนักมากสุด</option></select>') ?>
<div class="table-responsive">
<table class="table table-striped bg-white align-middle">
  <tr><th>รูป</th><th>วันที่</th><th>รายการ</th><th>หมวด</th><th>กก.</th><th>สถานะ</th><th>แต้ม</th><th>CO₂e ที่ลด</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= pic($r['image'], 56, '📦', true) ?></td><td><?= dt($r['created_at']) ?></td><td><?= e($r['item_name']) ?></td><td><?= e($r['category']) ?></td>
      <td><?= $r['weight_kg'] ?></td><td><?= STATUS[$r['status']] ?></td><td><?= $r['points'] ?></td><td><?= $r['co2_kg'] ?></td>
      <td><?php if ($r['status'] == 'pending'): ?>
        <form method="post"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="cancel" onclick="return confirm('ยกเลิกคำขอนี้?')">ยกเลิก</button></form>
      <?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php foot() ?>
