<?php

include "repositorio.php";
include "girComum.php";

$funcao = $_POST["funcao"];

if ($funcao == 'grava') {
    call_user_func($funcao);
}

if ($funcao == 'recupera') {
    call_user_func($funcao);
}

if ($funcao == 'recuperaUpload') {
    call_user_func($funcao);
}

if ($funcao == 'excluir') {
    call_user_func($funcao);
}

return;

function grava()
{
    $reposit = new reposit(); //Abre a conexão.   

    $diretorioPai = "../uploads/";

    // Aqui é definido quais tipos podem ser gravados no banco e na pasta
    $tipoArquivoPermitido = array(
        "pdf",
        "png",
        "jpg",
        "jpeg",
        "xls",
        "xlsx",
        "csv",
        "doc",
        "docx",
        "odt",
        "rtf",
        "html",
        "zip",
        "rar"
    );

    $idUpload = "uploadArquivo";
    $uploadArray = $_FILES['uploadArquivo'];
    $tamanho =  count($_FILES['uploadArquivo']['name']);
    $uniqidUpload = md5(uniqid(rand(), true));

    $diretorioUnicoPregao = "../uploads/pregoes/" . $uniqidUpload . "/";

    //Verifica a existência de todos os diretorios.
    verificaDiretorio($diretorioPai);
    verificaDiretorio($diretorioUnicoPregao);

    for ($i = 0; $i < $tamanho; $i++) {

        if (strpos($uploadArray["name"][$i], "-")) {  //Checa se o nome contém qualquer traço e substitui por underline.
            $uploadArray["name"][$i] = str_replace("-", "_", $uploadArray["name"][$i]);
            $uploadArray["name"][$i] = tiraAcento($uploadArray["name"][$i]);
        }

        $arrayUpload[$i] = array(
            "nome" => $uploadArray["name"][$i],
            "tipo" => $uploadArray["type"][$i],
            "nomeTemporario" => $uploadArray["tmp_name"][$i],
            "endereco" => $diretorioUnicoPregao,
            "idCampo" => $idUpload
        );
    }

    //XML DE UPLOAD:
    $nomeXml =  "ArrayOfUpload";
    $nomeTabela = "pregaoDocumento";
    if (sizeof($uploadArray) > 0) {
        $xmlUpload = '<?xml version="1.0"?>';
        $xmlUpload = $xmlUpload . '<' . $nomeXml . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';

        foreach ($arrayUpload as $chave) {
            $xmlUpload = $xmlUpload . "<" . $nomeTabela . ">";
            foreach ($chave as $campo => $valor) {
                if ($campo === "nomeTemporario") {
                    continue;
                }
                if (($campo === "nome") && ($valor != "")) {
                    $valor =  $uniqidUpload . "_" . $valor;
                }

                $xmlUpload = $xmlUpload . "<" . $campo . ">" . $valor . "</" . $campo . ">";
            }
            $xmlUpload = $xmlUpload . "</" . $nomeTabela . ">";
        }
        $xmlUpload = $xmlUpload . "</" . $nomeXml . ">";
    } else {
        $xmlUpload = '<?xml version="1.0"?>';
        $xmlUpload = $xmlUpload . '<' . $nomeXml . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
        $xmlUpload = $xmlUpload . "</" . $nomeXml . ">";
    }
    $xml = simplexml_load_string($xmlUpload);  //Transforma o xml em uma string.

    if ($xml === false) {
        $mensagem = "Erro na criação do XML de Upload ";
        echo "failed#" . $mensagem . ' ';
        return;
    }

    $xmlUpload = "'" . $xmlUpload . "'";

    moverArquivosParaPasta($idUpload, $uniqidUpload, $tipoArquivoPermitido, $diretorioUnicoPregao);



    // Gravação do formulário no banco de dados. 
    session_start();
    $codigo =  (int)$_POST['codigo'] ?: 0;
    $portal = (int)$_POST['portal'] ?: 0;
    $ativo = 1;
    $orgaoLicitante =  validaString($_POST['orgaoLicitante']);
    $numeroPregao = validaString($_POST['numeroPregao']);
    $dataPregao = validaData($_POST['dataPregao']);
    $horaPregao = validaString($_POST['horaPregao']);
    $oportunidadeCompra = validaString($_POST['oportunidadeCompra']);
    $objetoLicitado = validaString($_POST['objetoLicitado']);
    $observacao = validaString($_POST['observacao']);
    $usuario = validaString($_SESSION['login']);
    $pregaoGarimpado = 1;
    $condicao =  (int)$_POST['condicao'] ?: 0;
    $observacaoCondicao = validaString($_POST['observacaoCondicao']);
    $participaPregao = 1;
    $resumoPregao = validaString($_POST['resumoPregao']);
    $grupo =  (int)$_POST['grupo'] ?: 0;
    $responsavelPregao = (int)$_POST['responsavelPregao'] ?: 0;
    $valorEstimado = (float)virgulaParaPonto($_POST['valorEstimado']);
    $strArrayTarefa = $_POST['jsonTarefa'];
    $arrayTarefa = json_decode($strArrayTarefa, true);
    $xmlTarefa = "";
    $nomeXml = "ArrayOfTarefa";
    $nomeTabela = "pregaoDetalhe";
    if (sizeof($arrayTarefa) > 0) {
        $xmlTarefa = '<?xml version="1.0"?>';
        $xmlTarefa = $xmlTarefa . '<' . $nomeXml . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';

        foreach ($arrayTarefa as $chave) {
            $xmlTarefa = $xmlTarefa . "<" . $nomeTabela . ">";
            foreach ($chave as $campo => $valor) {
                if (($campo === "sequencialTarefa" || $campo === "tarefaId")) {
                    continue;
                }
                if ($campo  === "dataSolicitacao") {
                    if ($valor == "") {
                        continue;
                    }

                    $horario = explode(" ", $valor);
                    $horario = $horario[1];
                    $valor = str_replace('/', '-', $valor);
                    $valor = date("Y-m-d", strtotime($valor));
                    $valor = $valor . " " . $horario;
                }
                if (($campo === "dataFinal") || ($campo === "dataConclusao")) {
                    if ($valor == "") {
                        continue;
                    }
                    $valor = str_replace('/', '-', $valor);
                    $valor = date("Y-m-d", strtotime($valor));
                }
                $xmlTarefa = $xmlTarefa . "<" . $campo . ">" . $valor . "</" . $campo . ">";
            }
            $xmlTarefa = $xmlTarefa . "</" . $nomeTabela . ">";
        }
        $xmlTarefa = $xmlTarefa . "</" . $nomeXml . ">";
    } else {
        $xmlTarefa = '<?xml version="1.0"?>';
        $xmlTarefa = $xmlTarefa . '<' . $nomeXml . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
        $xmlTarefa = $xmlTarefa . "</" . $nomeXml . ">";
    }
    $xml = simplexml_load_string($xmlTarefa);
    if ($xml === false) {
        $mensagem = "Erro na criação do XML de tarefa!";
        echo "failed#" . $mensagem . ' ';
        return;
    }
    $xmlTarefa = "'" . $xmlTarefa . "'";

    $sql = "ntl.pregaoNaoIniciado_Atualiza
        $codigo,
        $portal,  
        $ativo,
        $orgaoLicitante, 
        $participaPregao, 
        $numeroPregao,
        $dataPregao,
        $horaPregao,
        $oportunidadeCompra, 
        $objetoLicitado, 
        $observacao, 
        $usuario, 
        $pregaoGarimpado,
        $xmlUpload, 
        $xmlTarefa,
        $resumoPregao,
        $grupo,
        $responsavelPregao,
        $valorEstimado,
        $condicao,
        $observacaoCondicao
        ";

    $reposit = new reposit();
    $result = $reposit->Execprocedure($sql);

    $ret = 'success';
    if ($result < 1) {
        $ret = 'failed';
    }
    echo $ret;
    return;
}

function recupera()
{
    if ((empty($_POST["codigo"])) || (!isset($_POST["codigo"])) || (is_null($_POST["codigo"]))) {
        $mensagem = "Nenhum parâmetro de pesquisa foi informado.";
        echo "failed#" . $mensagem . ' ';
        return;
    } else {
        $codigo = +$_POST["codigo"];
    }

    $sql = "SELECT codigo, portal, ativo, orgaoLicitante, objetoLicitado,
    numeroPregao, dataPregao, horaPregao, oportunidadeCompra, usuarioCadastro, observacao,
    condicao, observacaoCondicao, dataCadastro, resumoPregao , grupoResponsavel, responsavel,valorEstimado
    FROM Ntl.pregao
    WHERE (0=0) AND
    codigo = " . $codigo;

    $reposit = new reposit();
    $result = $reposit->RunQuery($sql);

    $out = "";
    if($row = $result[0])
    $codigo = (int)$row['codigo'];
    $portal = (int)$row['portal'];
    $ativo = (int)$row['ativo'];
    $orgaoLicitante = $row['orgaoLicitante'];
    $objetoLicitado = $row['objetoLicitado'];
    $oportunidadeCompra = $row['oportunidadeCompra'];
    $numeroPregao = $row['numeroPregao'];
    $dataPregao = $row['dataPregao'];
    $horaPregao = $row['horaPregao'];
    $usuarioCadastro = $row['usuarioCadastro'];
    $dataCadastro = $row['dataCadastro'];
    $observacao  = $row['observacao'];
    $condicao  = $row['condicao'];
    $observacaoCondicao  = $row['observacaoCondicao'];
    $resumoPregao = $row['resumoPregao'];
    $grupoResponsavel = $row['grupoResponsavel'];
    $responsavelPregao = $row['responsavel'];
    $valorEstimado = number_format($row['valorEstimado'], 2, ',', '.');

    //Montando array de tarefas  
    $reposit = "";
    $result = "";
    $sql = "SELECT GPD.codigo, GPD.tarefa, GPD.responsavel, GPD.dataFinal, GPD.dataSolicitacao, GPD.observacao, GPD.dataConclusao, GPD.tipo 
    FROM Ntl.pregaoDetalhe  GPD
    INNER JOIN Ntl.pregao GP ON GP.codigo = GPD.pregao
    WHERE (0=0) AND GP.codigo = " . $codigo;
    $reposit = new reposit();
    $result = $reposit->RunQuery($sql);

    $contadorTarefa = 0;
    $arrayTarefa = array();
    foreach($result as $row) {

        $tarefa = (int)$row['tarefa'];
        $responsavel = (int)$row['responsavel'];
        $dataFinal = validaDataRecupera($row['dataFinal']);
        $dataSolicitacao = $row['dataSolicitacao'];
        $dataConclusao = validaDataRecupera($row['dataConclusao']);
        $tipo = (int)$row['tipo'];

        if ($dataSolicitacao != "") {
            $horario = explode(" ", $dataSolicitacao);
            $horario = explode(":", $horario[1]);
            $dataSolicitacao = date("d-m-Y", strtotime($dataSolicitacao));
            $dataSolicitacao = str_replace('-', '/', $dataSolicitacao);
            $dataSolicitacao = $dataSolicitacao . " " . $horario[0] . ":" . $horario[1];
        }

        $observacaoPrePregao = $row['observacao'];

        $contadorTarefa = $contadorTarefa + 1;
        $arrayTarefa[] = array(
            "sequencialTarefa" => $contadorTarefa,
            "tarefa" => $tarefa,
            "responsavel" => $responsavel,
            "dataFinal" => $dataFinal,
            "dataSolicitacao" => $dataSolicitacao,
            "observacaoPrePregao" => $observacaoPrePregao,
            "dataConclusao" => $dataConclusao,
            "tipo" => $tipo
        );
    }

    $strArrayTarefa = json_encode($arrayTarefa);

    $out =   $codigo . "^" .
        $portal . "^" .
        $ativo . "^" .
        $orgaoLicitante . "^" .
        $objetoLicitado . "^" .
        $oportunidadeCompra . "^" .
        $numeroPregao . "^" .
        $dataPregao . "^" .
        $horaPregao . "^" .
        $usuarioCadastro . "^" .
        $dataCadastro . "^" .
        $observacao . "^" .
        $condicao . "^" .
        $observacaoCondicao  . "^" .
        $tipo . "^" .
        $resumoPregao . "^" .
        $grupoResponsavel . "^" .
        $responsavelPregao . "^" .
        $valorEstimado;

    if ($out == "") {
        echo "failed#";
        return;
    }

    echo "sucess#" . $out . "#" . $strArrayTarefa;
    return;
}


function excluir()
{

    $reposit = new reposit();
    $possuiPermissao = $reposit->PossuiPermissao("PREGOESNAOINICIADOS_ACESSAR|PREGOESNAOINICIADOS_EXCLUIR");

    if ($possuiPermissao === 0) {
        $mensagem = "O usuário não tem permissão para excluir!";
        echo "failed#" . $mensagem . ' ';
        return;
    }

    $codigo = $_POST["codigo"];

    if ((empty($_POST['codigo']) || (!isset($_POST['codigo'])) || (is_null($_POST['codigo'])))) {
        $mensagem = "Selecione um pregão.";
        echo "failed#" . $mensagem . ' ';
        return;
    }

    $result = $reposit->update('garimpaPregao' . '|' . 'ativo = 0' . '|' . 'codigo =' . $codigo);

    if ($result < 1) {
        echo ('failed#');
        return;
    }
    echo 'sucess#' . $result;
    return;
}

function recuperaUpload()
{

    $id = (int)$_POST['id'] ?: 0;
    $diretorioAlvo = "../uploads/";

    $sql = " SELECT codigo, nomeArquivo, tipoArquivo, endereco, idCampo, pregao 
    FROM Ntl.pregaoDocumento 
    WHERE (0=0) AND pregao = " . $id;
    $reposit = new reposit();
    $result = $reposit->RunQuery($sql);

    $contadorDocumento = 0;
    $arrayDocumentos = array();
    $out = "";
    foreach($result as $row) {

        $nomeArquivo = $row['nomeArquivo'];
        $tipoArquivo = $row['tipoArquivo'];
        $endereco = $row['endereco'];
        $idCampo = $row['idCampo'];


        $contadorDocumento = $contadorDocumento + 1;
        $arrayDocumentos[] = array(
            "nomeArquivo" => $nomeArquivo,
            "tipoArquivo" => $tipoArquivo,
            "endereco" => $endereco,
            "idCampo" => $idCampo
        );
    }


    $strArrayDocumentos = json_encode($arrayDocumentos);

    if ($strArrayDocumentos == "") {
        echo "failed#";
        return;
    }

    echo "sucess#" . $strArrayDocumentos;
    return;
}


function validaString($value)
{
    $null = 'NULL';
    if ($value == '')
        return $null;
    return '\'' . $value . '\'';
}

function validaNumero($value)
{
    if ($value == "") {
        $value = 'NULL';
    }
    return $value;
}


function validaData($value)
{
    if ($value == "") {
        $value = 'NULL';
        return $value;
    }
    $value = str_replace('/', '-', $value);
    $value = date("Y-m-d", strtotime($value));
    $value = "'" . $value . "'";
    return $value;
}

function validaDataRecupera($value)
{
    if ($value == "") {
        $value = '';
        return $value;
    }
    $value = date('d/m/Y', strtotime($value));
    return $value;
}

function validaVerifica($value)
{
    if ($value == "") {
        $value = NULL;
    }
    return $value;
}

function verificaDiretorio($enderecoPasta)
{

    /*Verifica se o diretório com o endereço especificado existe,
    se não, ele cria e atribui permissões de leitura e gravação. */

    if (!file_exists($enderecoPasta)) {
        mkdir($enderecoPasta, 0777, true);
        chmod($enderecoPasta, 0777);
    }
}

function moverArquivosParaPasta($campo, $uniqId, $tipoArquivoPermitido, $diretorioAlvo)
{


    /* Verifica a quantidade de imagens a serem upadas. E atribui as caracteristicas dessas 
    imagens através de um loop. */

    for ($x = 0; $x < count($_FILES[$campo]['name']); $x++) {

        $_FILES[$campo]['name'][$x] = str_replace("-", "_", $_FILES[$campo]['name'][$x]);  //Substitui qualquer traço por underline.
        $_FILES[$campo]['name'][$x] = tiraAcento($_FILES[$campo]['name'][$x]); //Retira qualquer acento.
        $nome = $uniqId . "_" . $_FILES[$campo]['name'][$x];
        $tamanho = $_FILES[$campo]['size'][$x];
        $nomeTemporario = $_FILES[$campo]['tmp_name'][$x];

        /* Caso a extensão da imagem esteja no array de tipos de arquivo permitidos ele
        executa a função de mover os arquivos para a pasta designada. */
        if ((in_array(pathinfo($nome, PATHINFO_EXTENSION), $tipoArquivoPermitido))) {
            move_uploaded_file($nomeTemporario, $diretorioAlvo . $nome);
        }
    }
}

function tiraAcento($string)
{
    return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $string);
}

function virgulaParaPonto($value)
{
    $aux = $value;
    $aux = str_replace('.', '', $aux);
    $aux = str_replace(',', '.', $aux);
    $aux = floatval($aux);
    if (!$aux) {
        $aux = 'null';
    }
    return $aux;
}