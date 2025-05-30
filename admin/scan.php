<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

$page_title = 'Scan QR Code';
include '../includes/header.php';

$db = new Database();
$conn = $db->getConnection();
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Scan QR Code</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <video id="preview" class="w-100"></video>
                    </div>
                    <form id="scanForm" method="POST" action="process_scan.php">
                        <input type="hidden" name="qr_data" id="qr_data">
                    </form>
                    <div class="text-center">
                        <button id="startButton" class="btn btn-primary me-2">
                            <i class="fas fa-camera me-2"></i>Start Scanner
                        </button>
                        <button id="stopButton" class="btn btn-secondary" style="display: none;">
                            <i class="fas fa-stop me-2"></i>Stop Scanner
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
<script>
let scanner = null;

document.getElementById('startButton').addEventListener('click', function() {
    scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
    
    scanner.addListener('scan', function(content) {
        document.getElementById('qr_data').value = content;
        document.getElementById('scanForm').submit();
    });
    
    Instascan.Camera.getCameras().then(cameras => {
        if (cameras.length > 0) {
            scanner.start(cameras[0]);
            this.style.display = 'none';
            document.getElementById('stopButton').style.display = 'inline-block';
        } else {
            alert('No cameras found');
        }
    }).catch(console.error);
});

document.getElementById('stopButton').addEventListener('click', function() {
    if (scanner !== null) {
        scanner.stop();
        this.style.display = 'none';
        document.getElementById('startButton').style.display = 'inline-block';
    }
});
</script>

<?php include '../includes/footer.php'; ?>