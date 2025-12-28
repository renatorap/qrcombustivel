<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PostoApp - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.js">
    </script>
    <script type="text/javascript" src="js/instascan.min.js"></script>

</head>
<body>


<video id="preview"></video>
		<script>
			const qrCodeScannerBtn = document.getElementById('Btn');
            const qrCodeScannerText = document.getElementById('Text');
            const qrCodePreview = document.getElementById('preview');
            //console.log(qrCodeScannerBtn);
            //console.log(qrCodeScannerText);
            //console.log(qrCodePreview);
            const scanner = new Instascan.Scanner({
              video: qrCodePreview,
              mirror: false
            });
            scanner.addListener('scan', content => {
              qrCodeScannerText.value = content;
              scanner.stop();
            });
            qrCodeScannerBtn.addEventListener('click', () => {
              if (scanner._scanner._active) {
                return scanner.stop();
              }
              Instascan.Camera.getCameras()
                .then(function(cameras) {
                  if (cameras.length) {
                    const camera = cameras[cameras.length - 1];
                    scanner.start(camera);
                    console.log(scanner);
                  } else {
                    alert('No cameras found.');
                  }
                })
                .catch(function(e) {
                  console.error(e);
                });
            });
			
		</script>
      <div>
          <p id="Text"></p>
      </div>
      <div>
        <button type="button" id="Btn">Close</button>
      </div>



</body>
</html>
