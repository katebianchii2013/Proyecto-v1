<?php

require_once("conexion.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

$con = conectar_bd();

// Comprobar que se envió un formulario por POST desde carga_datos
if (isset($_POST["envio"])) {
    $nombre_p =  $_POST["nombre_p"];
    $email = $_POST["email"];
    $contrasenia = $_POST["pass"];
    $rol = $_POST["usuario"];
   
    // Consultar si el usuario ya existe
    $existe_usr = consultar_existe_usr($con, $email);
    $existe_nom = consultar_existe_nom($con, $nombre_p);
    $verificar= verificar_codigo($con,$email);

    // Insertar datos si el usuario no existe
    insertar_datos($con, $nombre_p, $email, $contrasenia, $rol, $existe_usr, $existe_nom);
}

function consultar_existe_usr($con, $email) {
    $email = mysqli_real_escape_string($con, $email); // Escapar los campos para evitar inyección SQL
    $consulta_existe_usr = "SELECT email FROM usuario WHERE email = '$email'";
    $resultado_existe_usr = mysqli_query($con, $consulta_existe_usr);

    return mysqli_num_rows($resultado_existe_usr) > 0;
}

function sendVerificationCode($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ozaris08@gmail.com'; // Your email
        $mail->Password = 'atyo kobp ocmj zztp'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('ozaris08@gmail.com', 'Ozaris');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Código de Verificación';
        $mail->Body = "Su código de verificación es: <b>$code</b>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function consultar_existe_nom($con, $nombre_p) {
    $nombre_p = mysqli_real_escape_string($con, $nombre_p); // Escapar los campos para evitar inyección SQL
    $consulta_existe_nom = "SELECT nombre_p FROM usuario WHERE nombre_p = '$nombre_p'";
    $resultado_existe_nom = mysqli_query($con, $consulta_existe_nom);

    return mysqli_num_rows($resultado_existe_nom) > 0;
}

function verificar_codigo($con) {
    if (isset($_POST['verificar'])) {
        $codigo_ingresado = $_POST['codigo'];
        $email = $_POST['email']; // Get the email from the POST data

        $con = conectar_bd();
        $email = mysqli_real_escape_string($con, $email);
        $consulta = "SELECT token FROM persona WHERE email = 'alex.rodriguez@estudiante.ceibal.edu.uy'";

        $resultado = mysqli_query($con, $consulta);

        if (mysqli_num_rows($resultado) > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $codigo_almacenado = $fila['token'];

            if ($codigo_ingresado === $codigo_almacenado) {
                echo "Código de verificación correcto. Registro completado.";
                header("Location: index.php");
                exit();
            } else {
                echo "Código de verificación incorrecto.";
            }
        } else {
            echo "No se encontró el usuario asociado al correo electrónico.";
        }

        mysqli_close($con);
    }
}


function insertar_datos($con, $nombre_p, $email, $contrasenia, $rol, $existe_usr, $existe_nom) {
    if (!$existe_usr && !$existe_nom) {
        $email = mysqli_real_escape_string($con, $email);
        $contrasenia = password_hash($contrasenia, PASSWORD_DEFAULT);
        $verificationCode = bin2hex(random_bytes(3)); // Esto genera un código de 6 caracteres

        // Insertar en la tabla persona
        $consulta_insertar_persona = "INSERT INTO persona (nombre_p, email, contrasenia, rol, token) VALUES ('$nombre_p', '$email', '$contrasenia', '$rol', '$verificationCode')";

        if (mysqli_query($con, $consulta_insertar_persona)) {
            $id_per = mysqli_insert_id($con);

            // Insertar en la tabla usuario usando el ID de la persona
            $consulta_insertar_usuario = "INSERT INTO usuario (Id_per, nombre_p, email, contrasenia) VALUES ('$id_per', '$nombre_p', '$email', '$contrasenia')";
    
            if (mysqli_query($con, $consulta_insertar_usuario)) {
                $salida = consultar_datos($con);
                echo $salida;
                
                if (sendVerificationCode($email, $verificationCode)) {
                    echo "Código de verificación enviado a $email. Verifíquelo para completar el registro.";
                    header("Location: verification_form.html?email=" . urlencode($email));
                    exit();
                }
            } else {
                echo "Error al insertar en usuario: " . mysqli_error($con);
            }
        } else {
            echo "Error al insertar en persona: " . mysqli_error($con);
        }
    } else {
        echo "Usuario ya existe.";
    }
}
function consultar_datos($con) {
    $consulta = "SELECT * FROM usuario";
    $resultado = mysqli_query($con, $consulta);

    // Inicializo una variable para guardar los resultados
    $salida = "";

    // Si se encuentra algún registro de la consulta
    if (mysqli_num_rows($resultado) > 0) {
        // Mientras haya registros
        header("Location: iniciodesesion.html");
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $salida .= "id: " . $fila["Id_per"] . " - Nombre: " . $fila["nombre_p"] . " - Email: " . $fila["email"] . "<br> <hr>";
        }
    } else {
        $salida = "Sin datos";
    }

    return $salida;
    header("Location: index.php");
}

mysqli_close($con);
?>
