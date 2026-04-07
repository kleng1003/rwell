<?php
include_once('../include/template.php');
include_once('../include/connection.php');

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
							<?php
							if (isset($_GET['id'])) {
								$account_id = $_GET['id'];

								//write SQL to get user data
								$sqlquery = "SELECT * FROM resident_account WHERE account_id = $account_id";
								//Execute the sql

								$result = $con->query ($sqlquery);
								if ($result->num_rows > 0) {    
									while ($row = $result->fetch_assoc()) {
										$email = $row['email'];
										$id = $row['account_id'];

									}
								 ?>
							<form method="POST" action="send_email.php">
								<div class="form-group">
									<label>Email:</label>
									<input type="email" class="form-control" value="<?php echo $email; ?>" name="email" required="required"/>
								</div>
								<div class="form-group">
									<label>Subject</label>
									<input type="text" class="form-control" value="Account Approval" name="subject" required="required"/>
								</div>
								<div class="form-group">
									<label>Message</label>
									<input type="text" class="form-control" value="Your account has been Activated, You can now Log in to your account." name="message" required="required"/>
								</div>
								<div class="buttons" style="display: flex; align-items: center; justify-content: center;">
									<button style="margin:5px;" name="send" class="btn btn-primary"><span class="glyphicon glyphicon-envelope"></span> Send</button>
									<a style="margin:5px;" href="../Pages/accounts.php"><button type="button" class="btn btn-secondary">Cancel</button></a>
								</div>
								
							</form>
							<?php
								}
							}
							?>
                        </div>
						<?php
							if(ISSET($_SESSION['status'])){
								if($_SESSION['status'] == "ok"){
						?>
								<div class="alert alert-info"><?php echo $_SESSION['result']?>
									<a style="margin:5px;" href="../Pages/accounts.php"><button type="button" class="btn btn-primary">Back</button></a>
								</div>
						<?php
								}else{
						?>
								<div class="alert alert-danger"><?php echo $_SESSION['result']?></div>
						<?php
								}
								
								unset($_SESSION['result']);
								unset($_SESSION['status']);
							}
						?>
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
