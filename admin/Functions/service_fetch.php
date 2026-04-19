<?php
include_once('../include/connection.php');

$result = mysqli_query($con, "SELECT * FROM services ORDER BY created_at DESC");

while ($row = mysqli_fetch_assoc($result)) {
?>
<tr>
    <td><strong><?= $row['service_name']; ?></strong></td>
    <td><?= htmlspecialchars($row['category']); ?></td>
    <td><?= $row['description'] ?: '—'; ?></td>
    <td><?= $row['duration']; ?> mins</td>
    <td>₱<?= number_format($row['price'], 2); ?></td>
    <td>
        <span class="status-badge <?= $row['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
            <?= ucfirst($row['status']); ?>
        </span>
    </td>
    <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
    <td>
        <button class="btn btn-warning btn-sm editBtn" data-id="<?= $row['service_id']; ?>">
            <i class="fas fa-edit"></i>
        </button>

        <button class="btn btn-info btn-sm toggleBtn" data-id="<?= $row['service_id']; ?>">
            <i class="fas fa-power-off"></i>
        </button>

        <button class="btn btn-danger btn-sm deleteBtn" data-id="<?= $row['service_id']; ?>">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
<?php } ?>