<?php
require_once './models/Retiro.php';
require_once './models/Cuenta.php';
require_once './models/GestorCSV.php';
require_once './models/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class RetiroController extends Retiro implements IApiUsable
{

  public function CargarUno($request, $response, $args)
  {
    $parametros = $request->getParsedBody();

    if (!isset($parametros['idCuenta']) || !isset($parametros['importe'])) 
    {
        $mensaje = "Se esperaban los parametros 'idCuenta' e 'importe'.";
        $response = $response->withStatus(400);
        $payload = json_encode($mensaje);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    $idCuenta = $parametros['idCuenta'];
    $importe = $parametros['importe'];

    $auxRetiro = new Retiro();
    $auxRetiro->idCuenta = $idCuenta;
    if($importe < 0) 
    {
        $mensaje = "El importe a retirar no puede ser menor a 0.";
        $payload = json_encode($mensaje);
        $response = $response->withStatus(400);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    $auxRetiro->importe = $importe;
    $auxRetiro->fecha = new DateTime('now');

    $cuenta = Cuenta::obtenerCuentaPorId($idCuenta);

    if($cuenta)
    { 
        $auxRetiro->crearRetiro();
        Cuenta::actualizarSaldoDeCuenta($cuenta, $importe, "retiro");
        LogController::CargarUno($request, "Retiro");     
        $mensaje = "Retiro realizado con exito.";
        $payload = json_encode($mensaje);
        $response = $response->withStatus(200);
    }
    else
    {
        $mensaje = "Error, La cuenta no existe. Verifique los datos ingresados";
        $response = $response->withStatus(400);
        $payload = json_encode($mensaje);
    }

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');

  }
  

  public function TraerUno($request, $response, $args)
  {
    $id = $args['id'];
    $retiro = Retiro::obtenerRetiroPorId($id);

    if($retiro)
    {
      $payload = json_encode($retiro);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "Retiro inválido. Verifique los datos ingresados."));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
    
  }

  public function TraerTodos($request, $response, $args)
  {
    $lista = Retiro::obtenerTodos();
    if($lista)
    {
      $payload = json_encode(array("Lista de retiros" => $lista));
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');
    } 
    else 
    {
      $payload = json_encode(array("mensaje" => "No hay retiros."));
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


  public function TotalRetirosPorCuentaYMoneda($request, $response, $args)
  {
    if(!isset($_GET['tipoCuenta']) || !isset($_GET['moneda'])) 
    {
      $mensaje = "Falta los valores para Tipo de Cuenta y Moneda en los parametros";
      $response = $response->withStatus(400);
      $payload = json_encode($mensaje);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
   
    $auxTipo = $_GET['tipoCuenta'];
    $auxMoneda = $_GET['moneda'];

    if (empty($auxTipo) || empty($auxMoneda)) 
    {
      $mensaje = "Falta asignar los valores para Tipo de Cuenta y Moneda en los parametros";
      $response = $response->withStatus(400);
      $payload = json_encode($mensaje);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    date_default_timezone_set("America/Argentina/Buenos_Aires");
    $fechaHoy = new DateTime();  // Obtiene la fecha actual
    $fechaUnica = $fechaHoy->sub(new DateInterval('P1D'));

    if (isset($_GET['fecha'])) 
    {
      $fechaUnica = new DateTime($_GET['fecha']);
    }

    $auxTotalDeRetiros = Retiro::obtenerTodos();

    if (empty($auxTotalDeRetiros)) 
    {
      $mensaje = "Error. No hay retiros realizados.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    } 
    else 
    {
      $listaDeRetirosFiltrado = [];
      $auxCuenta; 
      foreach ($auxTotalDeRetiros as $retiro) 
      {
        $fechaAuxiliar = new DateTime($retiro->fecha);

        $auxCuenta = Cuenta::obtenerCuentaPorId($retiro->idCuenta);

        if($fechaUnica == $fechaAuxiliar )
        {
          if (strcmp($auxCuenta->tipoCuenta, $auxTipo) == 0 && strcmp($auxCuenta->moneda, $auxMoneda) == 0) 
          {     
              array_push($listaDeRetirosFiltrado, $retiro);  
          }
        }
      }

      if($listaDeRetirosFiltrado)
      {
        $totalRetirado = 0;
        foreach ($listaDeRetirosFiltrado as $retiro) 
        {
          $totalRetirado += $retiro->importe;
        }
        $mensaje = "El total retirado es: $".$totalRetirado;
        $payload = json_encode($mensaje);
        $response->getBody()->write($payload);
        $response = $response->withStatus(200);
        return $response->withHeader('Content-Type', 'application/json');           
      }
      else
      {
        $payload = json_encode(array("mensaje" => "No hay retiros para esa fecha de ese tipo y moneda."));
        $response->getBody()->write($payload);
        $response = $response->withStatus(400);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
  }



  public function ListarTotalRetirosPorIdUsuario($request, $response, $args)
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
      $retiros = Retiro::TraerRetirosPorUsuario($id);
      //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
      if($retiros)
      {
        $payload = json_encode($retiros);
        $response->getBody()->write($payload);
        $response = $response->withStatus(200);
        return $response->withHeader('Content-Type', 'application/json');           
      }
      else
      {
        $payload = json_encode(array("mensaje" => "No hay retiros realizados por el Usuario."));
        $response->getBody()->write($payload);
        $response = $response->withStatus(400);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
  }



  public function ListarTotalRetirosOrdenadosEntreDosFechas($request, $response, $args)
  {
    if(!isset($_GET['fechaInicio']) && !isset($_GET['fechaFinal'])) 
    {
      $mensaje = "Falta los valores para las fechas en los parametros";
      $response = $response->withStatus(400);
      $payload = json_encode($mensaje);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    if (empty($_GET['fechaInicio']) || empty($_GET['fechaFinal'])) 
    {
      $mensaje = "Falta asignar los valores para las fechas en los parametros";
      $response = $response->withStatus(400);
      $payload = json_encode($mensaje);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    $fechaInicio = new DateTime($_GET['fechaInicio']);
    $fechaFinal = new DateTime($_GET['fechaFinal']);

    $auxTotalDeRetiros = Retiro::obtenerTodos();

    if (empty($auxTotalDeRetiros)) 
    {
      $mensaje = "Error. No hay retiros realizados.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    } 
    else 
    {
      $listaDeRetirosEntreDosFechas = [];
      foreach ($auxTotalDeRetiros as $retiro) 
      {
        $fechaAuxiliar = new DateTime($retiro->fecha);

        if($fechaInicio < $fechaAuxiliar && $fechaAuxiliar < $fechaFinal)
        {
            array_push($listaDeRetirosEntreDosFechas, $retiro);
        }
      }
        //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
        //FALTA ORDENAR POR NOMBRE!!!!
      if($listaDeRetirosEntreDosFechas)
      {
        $payload = json_encode($listaDeRetirosEntreDosFechas);
        $response->getBody()->write($payload);
        $response = $response->withStatus(200);
        return $response->withHeader('Content-Type', 'application/json');           
      }
      else
      {
        $payload = json_encode(array("mensaje" => "No hay retiros entre las fechas ingresadas."));
        $response->getBody()->write($payload);
        $response = $response->withStatus(400);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
  }



  public function ListarRetirosPorTipoDeCuenta($request, $response, $args)
  {
    if(!isset($args['tipoCuenta'])) 
    {
      $mensaje = "Falta el tipo de cuenta en la URL";
      $response = $response->withStatus(400);
      $payload = json_encode($mensaje);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    $tipoCuenta = $args['tipoCuenta'];

    $retirosPorTipoCuenta = Retiro::obtenerRetiroPorTipoCuenta($tipoCuenta);
      //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
    if($retirosPorTipoCuenta)
    {
      $payload = json_encode($retirosPorTipoCuenta);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "No hay retiros para ese tipo de cuenta"));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }


  public function ListarRetirosPorMoneda($request, $response, $args)
  {
    if(!isset($args['moneda'])) 
    {
      $mensaje = "Falta el tipo de moneda en la URL";
      $response = $response->withStatus(400);
      $payload = json_encode($mensaje);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    $moneda = $args['moneda'];

    $retiroosPorMoneda = Retiro::obtenerRetiroPorMoneda($moneda);
      //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
    if($retiroosPorMoneda)
    {
      $payload = json_encode($retiroosPorMoneda);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "No hay retiros para ese tipo de cuenta"));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }


  
}

?>