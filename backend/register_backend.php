 <?php
    //Import PHPMailer classes into the global namespace
    //These must be at the top of your script, not inside a function
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    //Load Composer's autoloader (created by composer, not included with PHPMailer)
    require 'vendor/autoload.php';

    // Define function OUTSIDE the if block, at the top
            function test_input($data) {
                $data = trim($data);
                $data = htmlspecialchars($data);
                $data = stripslashes($data);
                return $data;
            }

            $fname = $lname = $email = $user_pass = "";
            $fnameErr = $lnameErr = $emailErr = $user_passErr = "";

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                
                //Validation of First Name 
                if (empty($_POST['fname'])) {
                    $fnameErr = "First name is required";
                } 
                else if (!preg_match("/^[a-zA-Z ]*$/",$_POST["fname"])){                             
                    $fnameErr = "Only letters and white space allowed";  
                }
                else {
                    $fnameErr = "";
                    $fname = test_input($_POST['fname']);
                }

                //Validation of Last Name
                if (empty($_POST['lname'])) {
                    $lnameErr = "Last name is required";
                } 
                else if (!preg_match("/^[a-zA-Z ]*$/",$_POST["lname"])){                             
                    $lnameErr = "Only letters and white space allowed";  
                }
                else {
                    $lnameErr = "";
                    $lname = test_input($_POST['lname']);
                }
                
                //Validation of Email
                if (empty($_POST['email'])) {
                    $emailErr = "Email is required";
                }
                else if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                    $emailErr = "Invalid email format";    
                }
                else {
                    $emailErr = "";
                    $email = test_input($_POST['email']);
                }
                
                //Validation of Password
                if (empty($_POST['user_pass'])) {
                    $user_passErr = "Password is required";
                } 
                else if (strlen($_POST['user_pass']) < 8) {
                    $user_passErr = "Minimum password length is eight";    
                }
                else {
                    $user_passErr = "";
                    $user_pass = $_POST['user_pass'];
                }

                //Insertion of Validated Form's Data into the Database 
                //Connect to the  database
                include("conn.php");
                //Verify email in the database
                $sql_email = $conn->prepare("SELECT * FROM Metro_School WHERE email=:email");
                $sql_email->bindParam(':email', $email);
                $sql_email -> execute();
                $result_mail = $sql_email->fetchAll();
                //Registered email is already exit in database
                if(!empty($result_mail)) {
                    $emailErr = "Your email is already existed";
                } 
                //Registered email is not exit in database and it is new account
                else {
                  
                    if($fname != "" && $lname != "" && $email != "" && $user_pass != "" &&  
                    $fnameErr == "" && $lnameErr == "" && $emailErr == "" && $user_passErr == "") {

                        // --- Generate a  unique token
                        $token = bin2hex(random_bytes(50));
                        $expires = date("U") + 1800; // Token expires in 30 minutes
                        
                        //Hash password
                        $hashed_password = password_hash($user_pass, PASSWORD_DEFAULT);

                        // --- Delete token in the database to ensure only for one email ---
                        $delete_token = $conn->prepare("DELETE FROM registration_token WHERE email=:email");
                        $delete_token->bindParam(':email', $email);
                        $delete_token -> execute();

                        // --- Insert token in the database for activation of registration ---
                        $insert_token = $conn->prepare("INSERT INTO registration_token (fname, lname, email, hashed_password, token, expires) VALUES (:fname, :lname, :email, :hpass, :token, :expires)");
                        $insert_token->bindParam(':fname', $fname);
                        $insert_token->bindParam(':lname', $lname);
                        $insert_token->bindParam(':email', $email);
                        $insert_token->bindParam(':hpass', $hashed_password);
                        $insert_token->bindParam(':token', $token);
                        $insert_token->bindParam(':expires', $expires);
                        $insert_token -> execute();
                            /*
                            //Insert Data Into the Database
                            $sql = $conn->prepare("INSERT INTO Metro_School (firstname, lastname, email, user_password) VALUES (:fname, :lname, :email, :upass)");
                            $sql->bindParam(':fname', $fname);
                            $sql->bindParam(':lname', $lname);
                            $sql->bindParam(':email', $email);
                            $sql->bindParam(':upass', $user_pass);
                            $sql->execute();
                             
                            if(isset($sql)) {
                            */  
                                $to = $email;
                                $confirm_link = "http://localhost:8080/15_g/successfully_registration.php?token=".$token;
                                $subject = "Confirm Registration Request";
                                $message = "Click the following link to confirm your registration to Metro School:<br><br>".$confirm_link;
                                //Inform registraction state using registered email
                                $mail = new PHPMailer(true);

                                try {
                                    //Server settings
                                    $mail->SMTPDebug = SMTP::DEBUG_OFF;
                                    $mail->isSMTP();
                                    $mail->Host       = 'smtp.gmail.com';
                                    $mail->SMTPAuth   = true;
                                    $mail->Username   = 'koz51751@gmail.com';
                                    $mail->Password   = 'aiwz ywzr vvcg mgcm';
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                    $mail->Port       = 587;

                                    //Recipients
                                    $mail->setFrom('koz51751@gmail.com', 'Metro School Registration');
                                    $mail->addAddress($to);

                                    //Content
                                    $mail->isHTML(true);
                                    $mail->Subject = $subject;
                                    $mail->Body    = $message;                           
                                    $mail->send();

                                    echo "
                                        <script>
                                            alert('Your Registration Confirm Link is successfully sent to your email.');
                                        </script>
                                        ";
                                    
                                } catch (Exception $e) {
                                    $messageErr = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                                }
                        /*   
                        } else {
                            echo "<script>alert('Error occured.');</script>";
                        } */                
                    } 
                }
                //close the connction
                $conn = null;             
            }                           
        ?>