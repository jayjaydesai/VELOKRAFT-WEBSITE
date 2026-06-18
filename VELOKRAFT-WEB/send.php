<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = htmlspecialchars($_POST["name"]);
    $email   = htmlspecialchars($_POST["email"]);
    $message = htmlspecialchars($_POST["message"]);
    $type    = htmlspecialchars($_POST["type"]);

    $to      = "jaydip.comline@gmail.com";
    $subject = "New Enquiry from $name – Velokraft";
    $body    = "Name: $name\nEmail: $email\nType: $type\n\nMessage:\n$message";
    $headers = "From: noreply@velokraft.eu\r\nReply-To: $email";

    if (mail($to, $subject, $body, $headers)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>