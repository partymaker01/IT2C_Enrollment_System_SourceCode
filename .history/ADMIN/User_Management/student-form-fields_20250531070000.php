<?php
// fallback for fields
function field($name) {
    global $s;
    return htmlspecialchars($s[$name] ?? '');
}
?>

<div class="mb-3"><label class="form-label">Student ID</label>
<input type="text" name="student_id" class="form-control" required value="<?= field('student_id') ?>"></div>

<div class="mb-3"><label class="form-label">First Name</label>
<input type="text" name="first_name" class="form-control" required value="<?= field('first_name') ?>"></div>

<div class="mb-3"><label class="form-label">Middle Name</label>
<input type="text" name="middle_name" class="form-control" value="<?= field('middle_name') ?>"></div>

<div class="mb-3"><label class="form-label">Last Name</label>
<input type="text" name="last_name" class="form-control" required value="<?= field('last_name') ?>"></div>

<div class="mb-3"><label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required value="<?= field('email') ?>"></div>

<div class="mb-3"><label class="form-label">Program</label>
<select name="program" class="form-select" required>
  <option disabled>Select Program</option>
  <?php foreach (['IT','HRMT','ECT','HST'] as $prog): ?>
    <option value="<?= $prog ?>" <?= field('program') === $prog ? 'selected' : '' ?>><?= $prog ?></option>
  <?php endforeach; ?>
</select></div>

<div class="mb-3"><label class="form-label">Year Level</label>
<select name="year_level" class="form-select" required>
  <option disabled>Select Year</option>
  <?php foreach (['1st Year','2nd Year','3rd Year'] as $year): ?>
    <option value="<?= $year ?>" <?= field('year_level') === $year ? 'selected' : '' ?>><?= $year ?></option>
  <?php endforeach; ?>
</select></div>

<div class="mb-3"><label class="form-label">Status</label>
<select name="status" class="form-select" required>
  <option disabled>Select Status</option>
  <?php foreach (['Active','Inactive'] as $stat): ?>
    <option value="<?= $stat ?>" <?= field('status') === $stat ? 'selected' : '' ?>><?= $stat ?></option>
  <?php endforeach; ?>
</select></div>