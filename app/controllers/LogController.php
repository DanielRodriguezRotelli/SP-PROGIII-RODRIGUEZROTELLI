<?php
require_once './models/Log.php';
require_once './controllers/UsuarioController.php';
require_once './models/AutentificadorJWT.php';

class LogController extends Log
{

  public static function CargarLogin($usuario, $operacion)
  {
    if($usuario)
    {
      $log = new Log();
      $log->idUsuario = $usuario->id;
      $log->operacion = $operacion;
      $log->crearLog();
    } 
    else 
    {
      echo "usuario inválido";
    }
  } 


  public static function CargarUno($request, $operacion)
  {
    $header = $request->getHeaderLine('Authorization'); 
    $token = trim(explode("Bearer", $header)[1]);
    $data = AutentificadorJWT::ObtenerData($token); 
    $usuario = UsuarioController::obtenerUsuario($data->nroDocumento);

    if($usuario)
    {
      $log = new Log();
      $log->idUsuario = $usuario->id;
      $log->operacion = $operacion;
      $log->crearLog();
    } 
    else 
    {
      echo "usuario inválido";
    }
  } 

 

}

?>