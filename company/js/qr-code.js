 document.getElementById("start-scan").addEventListener("click", function () {
            // Hide the Lottie animation
            document.getElementById("lottie-animation").style.display = 'none';

            // Show the video for QR scanning
            const video = document.getElementById("video");
            video.hidden = false;
            video.style.display = 'block';

            // Start QR code scanner by accessing the camera
            startQRCodeScanner();
        });

        function startQRCodeScanner() {
            const video = document.getElementById("video");
            const canvas = document.getElementById("canvas");

            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                }).then(function (stream) {
                    video.srcObject = stream;
                    video.play();

                    const context = canvas.getContext('2d');
                    video.addEventListener('play', () => {
                        const scanInterval = setInterval(() => {
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            context.drawImage(video, 0, 0, canvas.width, canvas.height);

                            // Assuming you are using jsQR for scanning
                            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                            const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

                            if (qrCode) {
                                // QR code successfully scanned
                                document.getElementById("video").srcObject.getTracks().forEach(track => track.stop()); // Stop video
                                clearInterval(scanInterval); // Stop scanning

                                // Show the result in the modal
                                document.getElementById("qrCodeDisplay").innerText = qrCode.data;
                                openModal('qrResultModal');
                            }
                        }, 500); // Scan every 500ms
                    });
                }).catch(function (err) {
                    console.error("Error accessing camera: ", err);
                    alert("Could not access camera. Please ensure you have allowed camera access in your browser settings.");
                });
            } else {
                alert("Camera not supported in this browser.");
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
