<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" >
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
	<title>Barangay Clearance</title>
</head>
<style>
    .container{
        margin-top: 30px;
    }
</style>

<body>
	<div class="container">
	    <div class="container-fluid">
			<div class="content-wrapper">


            
                <?php 
                // if the 'id' variable is set in the URL, we know that we need to edit a data
                require'../include/connection.php';



                if (isset($_GET['id'])) {
                    $resident_id = $_GET['id'];

                    // write SQL to get user data
                    $sqlquery = "SELECT * FROM tbl_request WHERE id = $resident_id";
                    //Execute the sql
                    $result = $con->query ($sqlquery);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $first_name = $row['first_name'];
                            $last_name = $row['last_name'];
                            $middle_name = $row['middle_name'];
                            $age = $row['age'];
                            $currentDate = date("m-d-Y");
                            $id = $row['id'];

                        }
                        echo"<br><br>";
                        echo"<div class='text-center'>
                        <img src='../images/logo.png' alt='logo' style='height: 11em; width: 11em;' srcset=''>
                        </div>";
                    echo "<h6 style = 'text-align:center; font-size:25px; padding:10px;'>Republic of the Philippines<br>
                    Province of Ilocos Sur<br>
                    Municipality of San Juan</h6>";

                    echo "<h5 style = 'text-align:center; font-size:25px; font-family:serif;'><b>OFFICE OF THE PUNONG BARANGAY</b></h5>
                        <h3 style = 'text-align:center; margin-top:30px; font-size:35px; font-family:serif; font-style:italic; text-decoration-line: underline;'><b>BARANGAY CLEARANCE</b></h3><br>";

                    echo "<b style = 'font-size:25px;'>TO WHOM IT MAY CONCERN:</b>";
                    echo "<p style = 'font-size:25px;'><br>&emsp;&emsp;&emsp;This is to certify that Mr./Ms. <b>".$first_name." ".$middle_name." ".$last_name." </b> ".$age.", <b></b> years of age is a resident of 
                        Barangay San Isidro, San Juan Ilocos Sur has no deregatory record filed in our Barangay Office.</p>";

                    echo "<br><p style = 'font-size:25px;'>&emsp;&emsp;&emsp;This is to certify further that he/she is known to me of good moral character and is a law abiding citizen.
                    Records of the barangay has shows that he/she has not committed nor been involoved in any kind of unlawful activities in this Barangay.</p>
                    ";
                    echo "<p style = 'font-size:25px;'>&emsp;&emsp;&emsp;This Barangay Clearance is issued upon the request of the above menntioned name for <b>ALL LEGAL PURPPOSES IT MAY SERVE.</b></p>";

                    echo "<p style = 'font-size:25px;'>&emsp;&emsp;&emsp;Issued this <b>
                    ".$currentDate."</b> at the Office of Punong Barangay, Barangay San Isidro, San Juan Ilocos Sur</p>";
                        } else{
                            // If the 'id' value is not valid, redirect the user back to official list.
                            header('Location: ../Pages/request.php');
                        }
                    }
                    
                    $query = "SELECT * FROM tbl_official WHERE Position = 'Barangay Captain' AND status = 'Active'";
                    $display = $con->query ($query);
                                    
                        if ($display) {
                            // output data of each row
                            while($row1 = mysqli_fetch_array($display)) {
                            $name = $row1["first_name"]." ".$row1["middle_name"]." ".$row1["last_name"];
                        
                        
                    echo "<br><br><p class='text-right' style= 'margin-right:10%; margin-bottom:0; font-size:25px;'><b style=font-size:30px;>".$name."</b><br></p><p class='text-right' style='margin-right:110px; font-size:25px;'> Punong Barangay</p>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

<?php
                                            
                

?>

<script type="text/javascript"> 
  window.addEventListener("load", window.print());
</script>
</body>
</html>