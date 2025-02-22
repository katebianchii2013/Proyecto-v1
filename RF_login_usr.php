<?php require("conexion.php");
include "iniciodesesion.html";
$con = conectar_bd();

if (isset($_POST["envio-in"])) {

    $email = $_POST["email"];
    $contrasenia = $_POST["pass"];

    // Llamada funcion login
    logear($con, $email, $contrasenia);
}

function logear($con, $email, $contrasenia  ) {

    session_start();

    $consulta_login = "SELECT * FROM persona WHERE email= '$email'";
    $resultado_login = mysqli_query($con, $consulta_login);

    if (mysqli_num_rows($resultado_login) > 0) {

        //se crea una variable con el objeto fetch asoc para acceder a las columnas que necesite
        $fila = mysqli_fetch_assoc($resultado_login);

        //asigno en una variable el campo pass de la BD...
        $password_bd = $fila["contrasenia"];

        //uso la funcion password_verify para comparar lo que ingresa el usuario con lo que tengo en la BD.
        if (password_verify($contrasenia, $password_bd)) {
      
            //si todo es correcto inicio la sesion y redirijo a la pagina del usuario logueado
            $_SESSION["email"] = $email;
     
            header("Location: index.php");
            exit();
        } else {

            echo "Contraseña incorrecta";
        }
    }
}