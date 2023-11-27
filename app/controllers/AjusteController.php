<?php
require_once './models/Ajuste.php';
require_once './models/Retiro.php';
require_once './models/Deposito.php';
require_once './models/Cuenta.php';
require_once './models/GestorCSV.php';
require_once './models/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class AjusteController extends Ajuste implements IApiUsable
{

  public function CargarUno($request, $response, $args)
  {
    $parametros = $request->getParsedBody();

    $tipoTransaccion = $parametros['tipoTransaccion'];
    $idTransaccion = $parametros['idTransaccion'];
    $importe = $parametros['importe'];
    $motivo = $parametros['motivo'];

    $auxAjuste = new Ajuste();
    $auxAjuste->tipoTransaccion = $tipoTransaccion;
    $auxAjuste->idTransaccion = $idTransaccion;
    $auxAjuste->motivo = $motivo;
    $auxAjuste->importe = $importe;
    if($importe < 0) 
    {
        $mensaje = "El importe a ajustar no puede ser menor a 0.";
        $payload = json_encode($mensaje);
        $response = $response->withStatus(400);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    $auxAjuste->fecha = new DateTime('now');

    if ($tipoTransaccion == "deposito") 
    {
      $auxDeposito = Deposito::obtenerDepositoPorId($idTransaccion);

      if (empty($auxDeposito)) 
      {
        $mensaje = "El deposito a ajustar no existe.";
        $payload = json_encode($mensaje);
        $response = $response->withStatus(400);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
      } 
      else 
      {
        $auxId = $auxDeposito[0]->idCuenta;
        $auxCuenta = Cuenta::obtenerCuentaPorId($auxId);

        if($auxCuenta)
        { 
          Deposito::ajustarDeposito($auxDeposito, $importe);
          $auxAjuste->crearAjuste();
          Cuenta::actualizarSaldoDeCuenta($auxCuenta, $importe, "deposito");
          LogController::CargarUno($request, "Ajuste de deposito");     
          $mensaje = "Ajuste realizado con exito.";
          $payload = json_encode($mensaje);
          $response = $response->withStatus(200);
        }
        else
        {
          $mensaje = "Error, cuenta no encontrada.";
          $response = $response->withStatus(400);
          $payload = json_encode($mensaje);
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
    
    if ($tipoTransaccion == "retiro") 
    {
      $auxRetiro = Retiro::obtenerRetiroPorId($idTransaccion);

      if (empty($auxRetiro)) 
      {
        $mensaje = "El retiro a ajustar no existe.";
        $payload = json_encode($mensaje);
        $response = $response->withStatus(400);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
      } 
      else 
      {
        $auxId = $auxRetiro[0]->idCuenta;
        $auxCuenta = Cuenta::obtenerCuentaPorId($auxId);

        if($auxCuenta)
        { 
          Retiro::ajustarRetiro($auxRetiro, $importe);
          $auxAjuste->crearAjuste();
          Cuenta::actualizarSaldoDeCuenta($auxCuenta, $importe, "retiro");
          LogController::CargarUno($request, "Ajuste de retiro");     
          $mensaje = "Ajuste realizado con exito.";
          $payload = json_encode($mensaje);
          $response = $response->withStatus(200);
        }
        else
        {
          $mensaje = "Error, cuenta no encontrada.";
          $response = $response->withStatus(400);
          $payload = json_encode($mensaje);
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
  }
  

  public function TraerUno($request, $response, $args)
  {
    $id = $args['id'];
    $deposito = Deposito::obtenerDepositoPorId($id);

    if($deposito)
    {
      $payload = json_encode($deposito);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "Deposito inválido. Verifique los datos ingresados."));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
    
  }

  public function TraerTodos($request, $response, $args)
  {
    $lista = Deposito::obtenerTodos();
    if($lista)
    {
      $payload = json_encode(array("Lista de depositos" => $lista));
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');
    } 
    else 
    {
      $payload = json_encode(array("mensaje" => "No hay depositos."));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }
    
  public function ModificarUno($request, $response, $args)
  {
    $datos = json_decode(file_get_contents("php://input"), true);
    $pedido = new Pedido();
    $pedido->id=$datos["id"]; 
    $pedido->idMesa=$datos["idMesa"]; 
    $pedido->codigoPedido=$datos["codigoPedido"]; 
    $pedido->idMozo=$datos["idMozo"]; 
    $pedido->nombreCliente=$datos["nombreCliente"];
    $pedido->fotoMesa=$datos["fotoMesa"]; 
    $pedido->horarioPautado=$datos["horarioPautado"];
    $pedido->estado=$datos["estado"]; 

    if(Pedido::modificarPedido($pedido))
    {
      $payload = json_encode(array("mensaje" => "Pedido modificado con exito"));
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');
    }
    else
    {
      $payload = json_encode(array("mensaje" => "No se pudo modificar el pedido. Verifique los datos ingresados."));  
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  public function BorrarUno($request, $response, $args)
  {   
    $id =  $args["id"];
    $pedidoABorrar=Pedido::obtenerPedidoPorId($id);
    if(Pedido::borrarPedido($id))
    {
      ProductoPedido::borrarProductoPedidoPorCodigo($pedidoABorrar->codigoPedido);
      $payload = json_encode(array("mensaje" => "Pedido cancelado con exito"));
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');
    }
    else
    {
      $payload = json_encode(array("mensaje" => "No se pudo cancelar el pedido. Verifique los datos ingresados."));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  
}

?>