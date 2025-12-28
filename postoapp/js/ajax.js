/**
  * Funcao para criar um objeto XMLHTTPRequest
  */
 function CriaRequest() {
     try{
         request = new XMLHttpRequest();
     }catch (IEAtual){

         try{
             request = new ActiveXObject("Msxml2.XMLHTTP");
         }catch(IEAntigo){

             try{
                 request = new ActiveXObject("Microsoft.XMLHTTP");
             }catch(falha){
                 request = false;
             }
         }
     }

     if (!request)
         alert("Seu Navegador nao suporta Ajax!");
     else
         return request;
 }

/**
* Funcao busca valor unitario
*/
function getDados() {

    // Declaracao de Variaveis
    var produto    = document.getElementById("produto").value;
    var valor_unitario  = document.getElementById("valor_unitario");
    var xmlreq     = CriaRequest();

    // Exibi a imagem de progresso
    //result.innerHTML = '<img src="circulo-progresso.jpg" width="50" height="50" />';

    // Iniciar uma requisição
    xmlreq.open("GET", "busca_val_comb.php?produto=" + produto, true);

    // Atribui uma funcao para ser executada sempre que houver uma mudanca de dado
    xmlreq.onreadystatechange = function(){

        // Verifica se foi concluido com sucesso e a conexao fechada (readyState=4)
        if (xmlreq.readyState == 4) {

            // Verifica se o arquivo foi encontrado com sucesso
            if (xmlreq.status == 200) {
                valor_unitario.value = xmlreq.responseText;
                valor_unitario_h.value = xmlreq.responseText;
            }else{
                valor_unitario.value = "Erro: " + xmlreq.statusText;
            }
        }
    };
    xmlreq.send(null);
}

function validaKManterior() {
    var km = document.getElementById("km_atual").value;
    var litragem = document.getElementById("litragem").value;
    var xmlreq = CriaRequest();

    xmlreq.open("GET", "valida_km.php?km=" + km + "&litragem=" + litragem, true);

    xmlreq.onreadystatechange = function() {
        if (xmlreq.readyState == 4 && xmlreq.status == 200) {
            var resposta = xmlreq.responseText.trim();
            if (resposta === "OK") {
                // Valor correto, não faz nada
            } else if (resposta === "NOK") {
                document.getElementById("km_atual").value = "";
                alert("Verifique o KM digitado!");
                document.getElementById("km_atual").focus();
            }
        }
    };
    xmlreq.send(null);
}


