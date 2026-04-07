<?php
include_once('../include/template.php');
include_once('../include/connection.php');

$sql = "SELECT s.*, 
               CONCAT(c.first_name,' ',c.last_name) AS customer_name,
               CONCAT(e.first_name,' ',e.last_name) AS employee_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.customer_id
        LEFT JOIN employees e ON s.employee_id = e.employee_id
        ORDER BY s.sale_date DESC";
$result = $con->query($sql);
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight: 600; color: #464660;">Sales</h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="../Functions/sale_add.php" class="btn btn-info"><i class="fas fa-receipt fa-fw"></i> Add Sale</a>
                <a href="../reports/sales-report.php" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Print Table</a>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="salesTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Sale Date</th>
                                <th>Customer</th>
                                <th>Employee</th>
                                <th>Total Amount</th>
                                <th>Payment Method</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['sale_date']); ?></td>
                                <td><?= htmlspecialchars($row['customer_name']); ?></td>
                                <td><?= htmlspecialchars($row['employee_name']); ?></td>
                                <td>₱<?= number_format($row['total_amount'], 2); ?></td>
                                <td><?= ucfirst($row['payment_method']); ?></td>
                                <td>
                                    <a href="sale-view.php?id=<?= $row['sale_id']; ?>" class="btn btn-warning" data-toggle="tooltip" title="View"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No sales found.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables & Tooltips -->
 <!-- jQuery -->
<script src="../js/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="../js/bootstrap.min.js"></script>
<!-- DataTables JS -->
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<script src="../js/dataTables/dataTables.responsive.js"></script>

<script>
$(document).ready(function() {
    $('#salesTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        searching: true
    });
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
