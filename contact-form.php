<?php

/**
 * Plugin Name: Contact Form Plugin
 * Description: A plugin to add a contact form to your WordPress site.
 * Version: 1.0
 */

register_activation_hook(__FILE__, 'myplugin_activate');
register_deactivation_hook(__FILE__, 'myplugin_deactivate');
add_shortcode('contact-form', 'display_contact_form');
add_action('admin_menu', 'add_contact_form_menu');

function dbConnect()
{
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=wordpress', 'root', '');
        // set the PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // echo "Connected successfully";
        return $pdo;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}

function myplugin_activate()
{
    $pdo = dbConnect();

    $sql = $pdo->prepare("CREATE TABLE wp_contact_form (
        id int(11) NOT NULL AUTO_INCREMENT,
        subject varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        first_name varchar(255) NOT NULL,
        mail varchar(255) NOT NULL,
        message text NOT NULL,
        send_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    )");
    $sql->execute();
}

function myplugin_deactivate()
{
    $pdo = dbConnect();

    $sql = $pdo->prepare("DROP TABLE wp_contact_form");
    $sql->execute();
}

function display_contact_form()
{
    $output = '
    <form id="contact-form" method="post" action="">
    <label for="subject">Subject :</label><br />
    <input type="text" id="subject" name="subject" required><br /><br />

    <label for="lastName">Last name :</label><br />
    <input type="text" id="lastName" name="lastName" required><br /><br />

    <label for="firstName">First name :</label><br />
    <input type="text" id="firstName" name="firstName" required><br /><br />

    <label for="mail">Mail :</label><br />
    <input type="email" id="mail" name="mail" required><br /><br />

    <label for="message">Message :</label><br />
    <textarea id="message" name="message" required></textarea><br /><br />

    <input type="submit" name="submit" value="Send">
    </form>';

    if (isset($_POST['submit'])) {
        if (process_contact_form()) {
            echo '
            <p class="woocommerce-message" role="alert">Your message has been sent successfully!</p>
            ';
        } else {
            echo '
            <pclass="woocommerce-error" role="alert">An error occurred while sending your message. Please try again later.</p>
            ';
        }
    }
    return $output;
}

function process_contact_form()
{
    if (isset($_POST['submit'])) {
        $pdo = dbConnect();

        $subject = $_POST['subject'];
        $lastName = $_POST['lastName'];
        $firstName = $_POST['firstName'];
        $mail = $_POST['mail'];
        $message = $_POST['message'];
        $send_date = date('Y-m-d H:i:s');

        try {
            $sql = $pdo->prepare("INSERT INTO wp_contact_form (subject,last_name,first_name,mail,message,send_date) VALUES (:subject,:lastName,:firstName,:mail,:message,:send_date)");
            $sql->bindParam('subject', $subject);
            $sql->bindParam('lastName', $lastName);
            $sql->bindParam('firstName', $firstName);
            $sql->bindParam('mail', $mail);
            $sql->bindParam('message', $message);
            $sql->bindParam('send_date', $send_date);
            $sql->execute();

            return true;
        } catch (PDOException $e) {
            // echo "Caught exception: " . $e->getMessage();
            return false;
        }
    }
}

function add_contact_form_menu()
{
    add_menu_page(
        'Contact Form Responses',
        'Contact Form',
        'manage_options',
        'contact-form-responses',
        'display_contact_form_responses'
    );
}

function display_contact_form_responses()
{
    $pdo = dbConnect();

    $sql = $pdo->prepare("SELECT * FROM wp_contact_form");
    $sql->execute();
    $results = $sql->fetchAll(PDO::FETCH_ASSOC);

    echo '<div style="max-width: 800px; margin: 0 auto;">';
    echo '<h1>Contact Form Responses</h1>';
    echo '<table style="border-collapse: collapse; width: 100%;">';
    echo '<tr style="background-color: #eee;"><th style="border: 1px solid #ddd; padding: 10px;">ID</th><th style="border: 1px solid #ddd; padding: 10px;">Subject</th><th style="border: 1px solid #ddd; padding: 10px;">Name</th><th style="border: 1px solid #ddd; padding: 10px;">Email</th><th style="border: 1px solid #ddd; padding: 10px;">Message</th><th style="border: 1px solid #ddd; padding: 10px;">Sent Date</th></tr>';

    foreach ($results as $result) {
        echo '<tr style="background-color: #fff;"><td style="border: 1px solid #ddd; padding: 10px;">' . $result['id'] . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 10px;">' . $result['subject'] . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 10px;">' . $result['first_name'] . ' ' . $result['last_name'] . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 10px;">' . $result['mail'] . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 10px;">' . $result['message'] . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 10px;">' . $result['send_date'] . '</td></tr>';
    }

    echo '</table>';
    echo '</div>';
}
