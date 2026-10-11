<?php
// Dashboard: ข้อมูลสรุป สถิติการใช้งาน และผลกระทบ Green Digital
require '../init.php';
need_admin();

$sum = one("SELECT COALESCE(SUM(weight_kg), 0) AS kg, COALESCE(SUM(co2_kg), 0) AS co2 FROM recycles WHERE status = 'done'");
$byMonth = all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, SUM(co2_kg) AS v FROM recycles WHERE status = 'done' GROUP BY m ORDER BY m");
$byCat = all("SELECT c.name, SUM(r.weight_kg) AS v FROM recycles r JOIN categories c ON c.id = r.category_id WHERE r.status = 'done' GROUP BY c.name");
$members = all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS v FROM users WHERE role = 'user' GROUP BY m ORDER BY m");
$notis = all('SELECT * FROM notifications WHERE user_id IS NULL AND is_read = 0 ORDER BY id DESC LIMIT 5');

head('Dashboard');
?>
<div class="row">
  <?php box('👥 สมาชิกทั้งหมด', val("SELECT COUNT(*) FROM users WHERE role = 'user'") . ' คน') ?>
  <?php box('🚫 สมาชิกที่ถูกระงับ', val("SELECT COUNT(*) FROM users WHERE status = 'suspended'") . ' คน') ?>
  <?php box('⏳ คำขอรีไซเคิลรอตรวจรับ', val("SELECT COUNT(*) FROM recycles WHERE status = 'pending'") . ' รายการ') ?>
  <?php box('💬 ข้อความแชทที่ยังไม่อ่าน', val('SELECT COUNT(*) FROM messages WHERE sender_id = user_id AND is_read = 0') . ' ข้อความ') ?>
  <?php box('📦 E-Waste ที่รีไซเคิล', $sum['kg'] . ' กก.') ?>
  <?php box('🌍 คาร์บอนที่ลดได้', $sum['co2'] . ' kgCO₂e') ?>
  <?php box('🌳 เทียบเท่าต้นไม้', round($sum['co2'] / TREE_KG, 1) . ' ต้น') ?>
  <?php box('🧮 ฟุตพริ้นท์เฉลี่ยปีนี้', number_format(val('SELECT COALESCE(AVG(total_kg), 0) FROM footprints WHERE year = YEAR(NOW())')) . ' kg') ?>
</div>
<div class="row">
  <div class="col-lg-8"><?php chart('คาร์บอนที่ลดได้รายเดือน (kgCO₂e)', 'bar', array_column($byMonth, 'm'), array_column($byMonth, 'v')) ?></div>
  <div class="col-lg-4"><?php chart('น้ำหนักที่รีไซเคิลตามหมวด (กก.)', 'doughnut', array_column($byCat, 'name'), array_column($byCat, 'v')) ?></div>
  <div class="col-lg-8"><?php chart('สมาชิกใหม่รายเดือน (คน)', 'line', array_column($members, 'm'), array_column($members, 'v')) ?></div>
  <div class="col-lg-4">
    <div class="card card-body mb-3">
      <h6>🔔 แจ้งเตือนที่ยังไม่อ่าน</h6>
      <?php foreach ($notis as $n): ?>
        <div class="border-top py-1"><b><?= e($n['title']) ?></b><br><small><?= e($n['message']) ?></small></div>
      <?php endforeach; ?>
      <a class="mt-2" href="../notifications.php">ดูทั้งหมด</a>
    </div>
  </div>
</div>
<?php foot() ?>
