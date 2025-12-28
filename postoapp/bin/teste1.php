<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste</title>
    <script src="https://code.jquery.com/jquery-3.6.3.js"></script>
</head>
<body>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<form action="">
                <!-- LITRAGEM -->
                <div class="form-floating mb-1">
                    <input type="number" class="form-control form-control-sm" id="litragem" step="0.001" min="0.001" required/>
                    <label for="litragem">Litragem</label>
                </div>
                <!-- VALOR UNITÁRIO -->
                <div class="form-floating mb-1">
                    <input type="number" class="form-control form-control-sm" id="valor_unitario" required/>
                    <label for="valor_unitario">Valor Unitário</label>
                </div>
                <!-- VALOR TOTAL -->
                <div class="form-floating mb-1">
                    <input type="text" class="form-control form-control-sm" id="valor_total" disabled required/>
                    <label for="valor_total">Valor Total</label>
                </div>

</form>

</body>
<script>

$('input[type="number"]').keyup(function(){
    //Receber os 2 valores, substituir vírgulas por ponto (replace) e converter para float (decimal)
    var num1 = parseFloat($('#litragem').val().replace(',','.'));
    var num2 = parseFloat($('#valor_unitario').val().replace(',','.'));
    //Somar os valores digitados e exibir o resultado preservando 3 dígitos após o ponto
    parseFloat($('#valor_total').val((num1 * num2).toFixed(2).replace('.',',')));
});

</script>

</html>