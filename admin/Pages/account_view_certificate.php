<?php
include_once('../include/connection.php');
include_once('../include/template.php');

if (isset ($_GET['id'])) {
    $accountID = $_GET['id'];
    // write delete query
    $sql = "SELECT `certificate` FROM `resident_account` WHERE account_id='$accountID'";
    // Execute the query
    $result = $con->query ($sql);
    if ($result->num_rows > 0) {
        while($row = mysqli_fetch_array($result)) {
            ?>
            <div id="wrapper">
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row">
                    
                        <div class="col-lg-12">
                            
                            <h1 class="page-header" style="font-weight: 600; color: #464660;"><a style="color: #EFEAD8;" href="accounts.php"><i class="fas fa-arrow-circle-left back"></i></a><strong> Certificate/ID</strong></h1>
                        </div>   
                        <!-- /.col-lg-12 -->
                    </div>
                    <!-- /.row -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <!-- /.panel-heading -->
                                <div class="panel-body">
                                    <img src="<?php echo "../images/certificates/" .$row["certificate"]; ?>" width="600px" height="800px" alt="Image">
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
        <?php
        }
        // echo '<meta http-equiv="refresh" content= "0;URL=../Pages/accounts.php" />';
    }else{
        echo "Error:" . $sql . "<br>" . $con->error;
    }
}
?>
