<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PostoApp - QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script type="text/javascript" src="js/instascan.min.js"></script>

</head>
<body>

<video id="preview" width="300px" height="300px"></video>
		<script>
			let scanner = new Instascan.Scanner(
				{
					video: document.getElementById('preview'),
					mirror: false,
					backgroundScan: false,
				}
			);
			scanner.addListener('scan', function(content) {
				scanner.stop();
				//alert('Sucesso!');
				self.close();
				window.location.href = "pump_capt.php?cod=" + content;
				
			});

     Instascan.Camera.getCameras().then(function (cameras) {
        let selectedCamera;

        // Tratamento para pegar a camera traseira do smartphone
        if (cameras.length > 0) {
           for (let c = 0; c < cameras.length; c++) {
              if (cameras[c].name.indexOf('back') != -1) {
                  selectedCamera = cameras[c];
              }
           }
           
           if (!selectedCamera) selectedCamera = cameras[0];
        }
        
         if (selectedCamera) {
             scanner.start(selectedCamera);
             
         } else {
					console.error("Não existe câmera no dispositivo!");
				}
			});
			
		</script>

</body>
</html>
