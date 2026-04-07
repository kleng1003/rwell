<?php
include_once('../include/template.php');
include_once('../include/connection.php');
$sql = "SELECT * FROM `tbl_resident_account`";
$result = $con->query($sql);
?>

<div id="wrapper">
    <div id="page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header" style="font-weight: 600; color: #464660;"><strong> Resident Accounts</strong></h1>
                </div>   
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="data">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <?php
                                    if($result->num_rows > 0){
                                        while($row = mysqli_fetch_array($result)) {

                                    ?>
                                    <tr>
                                        <td><?php echo $row["first_name"] , " " ,$row["middle_name"] , " " , $row["last_name"] ?></td>
                                        <td><?php echo $row["email"]?></td>
                                        <td><?php echo $row["contact"]?></td>
                                        <td><?php echo $row["status"]?></td>
                                        <td><?php echo $row["created_at"]?></td>
                                        <td><?php 
                                        if($row['status'] == "inactive" || $row['status'] == "pending") {?>
                                            <!-- <a href="../sendemail/index.php?id=<?php echo $row["id"]; ?>"><button type="button" class="btn btn-success">Notify</button></a> -->
                                            <a href="../Functions/resident_account_approval.php?id=<?php echo $row["id"]; ?>"><button type="button" class="btn btn-success">Activate</button></a>
                                            <?php 
                                        } elseif($row['status'] == "active") { ?>
                                                <a href="../Functions/resident_account_deactivate.php?id=<?php echo $row["id"]; ?>"><button type="button" class="btn btn-warning">Deactivate</button></a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php   }
                                        }
                                    ?>
                                </table>
                            </div>
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div> 
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /#page-wrapper -->
</div>
<!-- /#wrapper -->
