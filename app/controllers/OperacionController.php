<?php
require_once './models/Retiro.php';
require_once './models/Deposito.php';
require_once './models/Cuenta.php';
require_once './models/Usuario.php';
require_once './models/Operacion.php';
require_once './models/GestorCSV.php';
require_once './models/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class OperacionController extends Operacion 
{

    public function ListarOperacionesPorUsuario($request, $response, $args)
    {
        if(!isset($args['idUsuario'])) 
        {
            $mensaje = "Falta el id de Usuario en la URL.";
            $response = $response->withStatus(400);
            $payload = json_encode($mensaje);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        $id = $args['idUsuario'];

        $auxUsuario = Usuario::obtenerUsuarioPorId($id);

        if (empty($auxUsuario)) 
        {
            $mensaje = "Error. El usuario no existe. Verifique los datos ingresados.";
            $payload = json_encode($mensaje);
            $response = $response->withStatus(400);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        } 
        else 
        {

            $depositos = Deposito::TraerDepositosPorUsuario($id);
            $retiros = Retiro::TraerRetirosPorUsuario($id);
            $operaciones = [];
            foreach ($depositos as $deposito) 
            {
                array_push($operaciones, $deposito);  
            }
            foreach ($retiros as $retiro) 
            {
                array_push($operaciones, $retiro);  
            }
        
            //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
            if($operaciones)
            {
                $payload = json_encode($operaciones);
                $response->getBody()->write($payload);
                $response = $response->withStatus(200);
                return $response->withHeader('Content-Type', 'application/json');           
            }
            else
            {
                $payload = json_encode(array("mensaje" => "No hay operaciones realizados por el Usuario."));
                $response->getBody()->write($payload);
                $response = $response->withStatus(400);
                return $response->withHeader('Content-Type', 'application/json');
            }
        }
    }
}