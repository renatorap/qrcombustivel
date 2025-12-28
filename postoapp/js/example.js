/**
* Funcao para enviar os dados
*/
function fComb() {

    var placa_veiculo   = document.getElementById("placa_veiculo").value;
    var condutor        = document.getElementById("condutor").value;
    var produto         = document.getElementById("produto").value;
    var data            = document.getElementById("data").value;
    var hora            = document.getElementById("hora").value;
    var km_atual        = document.getElementById("km_atual").value;
    var litragem        = document.getElementById("litragem").value;
    var valor_unitario  = document.getElementById("valor_unitario").value;
    var valor_total     = document.getElementById("valor_total").value;

    var xmlreq          = CriaRequest();

    // Exibi a imagem de progresso
    //result.innerHTML = '<img src="circulo-progresso.jpg" width="50" height="50" />';

    // Iniciar uma requisição
    xmlreq.open("GET", "grava_abast.php?placa_veiculo=" + placa_veiculo + "&condutor=" + condutor + "&produto=" + produto + "&data=" + data + "&hora=" + hora + "&km_atual=" + km_atual + "&litragem=" + litragem + "&valor_unitario=" + valor_unitario + "&valor_total=" + valor_total, true);

    // Atribui uma funcao para ser executada sempre que houver uma mudanca de dado
    xmlreq.onreadystatechange = function(){

        // Verifica se foi concluido com sucesso e a conexao fechada (readyState=4)
        if (xmlreq.readyState == 4) {

            // Verifica se o arquivo foi encontrado com sucesso
            if (xmlreq.status == 200) {
                alerta.value = xmlreq.responseText;
            }else{
                alerta.value = "Erro: " + xmlreq.statusText;
            }
        }
    };
    xmlreq.send(null);
}
