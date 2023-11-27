<?php
require_once './models/Deposito.php';
require_once './models/Cuenta.php';
require_once './models/Usuario.php';
require_once './models/GestorCSV.php';
require_once './models/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class DepositoController extends Deposito implements IApiUsable
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

    $auxDeposito = new Deposito();
    $auxDeposito->idCuenta = $idCuenta;
    if($importe < 0) 
    {
        $mensaje = "El importe a depositar no puede ser menor a 0.";
        $payload = json_encode($mensaje);
        $response = $response->withStatus(400);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    $auxDeposito->importe = $importe;
    $auxDeposito->fecha = new DateTime('now');

    if(file_exists($_FILES["fotoDeposito"]["tmp_name"]))
    {
      $auxDeposito->fotoDeposito = $this->GuardarFoto($auxDeposito);
    } 
    else 
    {
      $auxDeposito->fotoDeposito = null;
    }

    $cuenta = Cuenta::obtenerCuentaPorId($idCuenta);

    if($cuenta)
    { 
        $auxDeposito->crearDeposito();
        Cuenta::actualizarSaldoDeCuenta($cuenta, $importe, "deposito");
        LogController::CargarUno($request, "Deposito");     
        $mensaje = "Deposito realizado con exito.";
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



  public function TotalDepositadoPorCuentaYMoneda($request, $response, $args)
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

    $auxTotalDeDepositos = Deposito::obtenerTodos();

    if (empty($auxTotalDeDepositos)) 
    {
      $mensaje = "Error. No hay depositos realizados.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    } 
    else 
    {
      $listaDeDepositosFiltrado = [];
      $auxCuenta; 
      foreach ($auxTotalDeDepositos as $deposito) 
      {
        $fechaAuxiliar = new DateTime($deposito->fecha);

        $auxCuenta = Cuenta::obtenerCuentaPorId($deposito->idCuenta);

        if($fechaUnica == $fechaAuxiliar )
        {
          if (strcmp($auxCuenta->tipoCuenta, $auxTipo) == 0 && strcmp($auxCuenta->moneda, $auxMoneda) == 0) 
          {     
              array_push($listaDeDepositosFiltrado, $deposito);  
          }
        }
      }

      if($listaDeDepositosFiltrado)
      {
        $totalDepositado = 0;
        foreach ($listaDeDepositosFiltrado as $deposito) 
        {
          $totalDepositado += $deposito->importe;
        }
        $mensaje = "El total depositado es: $".$totalDepositado;
        $payload = json_encode($mensaje);
        $response->getBody()->write($payload);
        $response = $response->withStatus(200);
        return $response->withHeader('Content-Type', 'application/json');           
      }
      else
      {
        $payload = json_encode(array("mensaje" => "No hay depositos para esa fecha de ese tipo y moneda."));
        $response->getBody()->write($payload);
        $response = $response->withStatus(400);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
  }



  public function ListarTotalDepositosPorIdUsuario($request, $response, $args)
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
      //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
      if($depositos)
      {
        $payload = json_encode($depositos);
        $response->getBody()->write($payload);
        $response = $response->withStatus(200);
        return $response->withHeader('Content-Type', 'application/json');           
      }
      else
      {
        $payload = json_encode(array("mensaje" => "No hay depositos realizados por el Usuario."));
        $response->getBody()->write($payload);
        $response = $response->withStatus(400);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
  }



  public function ListarTotalDepositosOrdenadosEntreDosFechas($request, $response, $args)
  {
    if(!isset($_GET['fechaInicio']) || !isset($_GET['fechaFinal'])) 
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

    $auxTotalDeDepositos = Deposito::obtenerTodos();

    if (empty($auxTotalDeDepositos)) 
    {
      $mensaje = "Error. No hay depositos realizados.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    } 
    else 
    {
      $listaDeDepositosEntreDosFechas = [];
      foreach ($auxTotalDeDepositos as $deposito) 
      {
        $fechaAuxiliar = new DateTime($deposito->fecha);

        if($fechaInicio < $fechaAuxiliar && $fechaAuxiliar < $fechaFinal)
        {
            array_push($listaDeDepositosEntreDosFechas, $deposito);
        }
      }
        //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
        //FALTA ORDENAR POR NOMBRE!!!!
      if($listaDeDepositosEntreDosFechas)
      {
        $payload = json_encode($listaDeDepositosEntreDosFechas);
        $response->getBody()->write($payload);
        $response = $response->withStatus(200);
        return $response->withHeader('Content-Type', 'application/json');           
      }
      else
      {
        $payload = json_encode(array("mensaje" => "No hay depositos entre las fechas ingresadas."));
        $response->getBody()->write($payload);
        $response = $response->withStatus(400);
        return $response->withHeader('Content-Type', 'application/json');
      }
    }
  }



  public function ListarDepositosPorTipoDeCuenta($request, $response, $args)
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

    $depositosPorTipoCuenta = Deposito::obtenerDepositoPorTipoCuenta($tipoCuenta);
      //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
      //FALTA ORDENAR POR NOMBRE!!!!
    if($depositosPorTipoCuenta)
    {
      $payload = json_encode($depositosPorTipoCuenta);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "No hay depositos para ese tipo de cuenta"));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }


  public function ListarDepositosPorMoneda($request, $response, $args)
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

    $depositosPorMoneda = Deposito::obtenerDepositoPorMoneda($moneda);
      //ARREGLAR LOS MENSAJES DE SALIDA!!!!!
      //FALTA ORDENAR POR NOMBRE!!!!
    if($depositosPorMoneda)
    {
      $payload = json_encode($depositosPorMoneda);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "No hay depositos para ese tipo de cuenta"));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }


  public static function GuardarFoto($deposito)
  {
    $auxCuenta = Cuenta::obtenerCuentaPorId($deposito->idCuenta);

    $carpetaFotos = ".".DIRECTORY_SEPARATOR."ImagenesDeDepositos/2023".DIRECTORY_SEPARATOR;
    if(!file_exists($carpetaFotos))
    {
        mkdir($carpetaFotos, 0777, true);
    }
  
    $nombreDeArchivo = $auxCuenta->tipoCuenta."-".$auxCuenta->id."-".$deposito->id;
    $destino = $carpetaFotos . $nombreDeArchivo . ".jpg";
    $tmpName = $_FILES["fotoDeposito"]["tmp_name"];

    if (move_uploaded_file($tmpName, $destino)) 
    {
        echo "<br>La foto se guardó correctamente.";
    } 
    else 
    {
        echo "<br>La foto no pudo guardarse.";
    }

    return $destino;
  }

}

?>