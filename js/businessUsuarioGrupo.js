function gravaUsuarioGrupo(id, ativo, descricao, callback) {
    $.ajax({
        url: 'js/sqlscopeUsuarioGrupo.php',
        dataType: 'html', //tipo do retorno
        type: 'post', //metodo de envio
        data: {funcao: "grava", id:id, ativo:ativo, descricao:descricao},   
        success: function (data) {
        callback(data);
        } 
    }); 
}
  
function recuperaUsuarioGrupo(id, callback) {
    $.ajax({
        url: 'js/sqlscopeUsuarioGrupo.php', //caminho do arquivo a ser executado
        dataType: 'html', //tipo do retorno
        type: 'post', //metodo de envio
        data: {funcao: 'recupera', id: id}, //valores enviados ao script      
        success: function (data) {
            callback(data); 
        }
    });

    return;
}
  
function excluirUsuarioGrupo(id, callback) {
    $.ajax({
        url: 'js/sqlscopeUsuarioGrupo.php', //caminho do arquivo a ser executado
        dataType: 'html', //tipo do retorno
        type: 'post', //metodo de envio
        data: {funcao: 'excluir', id: id}, //valores enviados ao script      
        success: function (data) {
            callback(data); 
        }
    });
}
