<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Stream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/css/livestream.css') }}">
</head>

<body>
    @php($ecommerceLogo = getWebConfig('company_web_logo'))
    <header>
        <div class="logo">
            <img src="{{ getValidImage('storage/app/public/company/' . $ecommerceLogo, type: 'backend-logo') }}"
                alt="Logo">
        </div>
        <div class="theme-toggle" id="themeToggle"><i class="fa-solid fa-moon"></i></div>
    </header>

    <div class="container">
        <!-- Left: Video + Details -->
        <div class="video-section">
            <div style="position: relative;">
                <video id="video" controls autoplay></video>
                <div class="video-overlay">
                    <img src="{{ getValidImage('storage/app/public/company/' . $ecommerceLogo, type: 'backend-logo') }}"
                        alt="Logo" class="overlay-logo">
                    <div class="live-badge">
                        <span class="live-dot"></span> LIVE STREAM
                    </div>
                </div>
            </div>

            <!-- Puja Details -->
            <div class="puja-details">
                <h2>महाकाल अभिषेक पूजा</h2>
                <p><strong>Venue:</strong> श्री गज महालक्ष्मी माताजी मंदिर, नई पेठ, छत्री चौक, उज्जैन, मध्य प्रदेश</p>
                <p><strong>Date:</strong> 16,Aug,Saturday</p>
                <p><strong>Time:</strong> सुबह 6:00 बजे</p>
                <p id="pujaDescription">
                    इस विशेष अवसर पर महाकालेश्वर ज्योतिर्लिंग का अभिषेक एवं भव्य आरती का सीधा प्रसारण।
                </p>
            </div>
        </div>

        <!-- Right: Puja & Chadhava List -->
        <div class="sidebar">
            <h2>बुक करने योग्य सेवाएं</h2>
            @foreach ($pujaList as $poojaD)
                <div class="puja-item">
                    <img src="{{ getValidImage(path: 'storage/app/public/pooja/thumbnail/' . $poojaD->service->thumbnail) }}"
                        alt="{{ $poojaD->service->name }}">
                    <div class="puja-info">
                        <h3>{{ $poojaD->service->name }}</h3>
                        <p>{{ $poojaD->service->pooja_venue }}</p>
                        <p>
                            @if ($poojaD->booking_date)
                                {{ date('d,M,l', strtotime($poojaD->booking_date)) }}
                            @endif
                        </p>
                        <a href="{{ route('epooja', $poojaD->service->slug) }}" class="book-btn">अभी बुक करें</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- FLV.js CDN -->
    <!-- FLV.js & HLS.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/flv.js@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

    <script>
        const video = document.getElementById('video');
        const iscomplete = @json($iscomplete ?? false);
        let videoSrc;

        if (iscomplete) {
            // Completed stream (FLV recorded file)
            videoSrc = 'https://stream.mahakal.com/pooja/{{ $liveKey }}.flv';

            if (flvjs.isSupported()) {
                const flvPlayer = flvjs.createPlayer({
                    type: 'flv',
                    url: videoSrc
                });
                flvPlayer.attachMediaElement(video);
                flvPlayer.load();
                flvPlayer.play();
            } else {
                console.error("FLV.js is not supported in this browser.");
            }

        } else {
            // Live stream (HLS)
            videoSrc = 'https://stream.mahakal.com/live/{{ $liveKey }}.m3u8';

            if (Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(videoSrc);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    video.play();
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Safari native support
                video.src = videoSrc;
                video.addEventListener('loadedmetadata', () => {
                    video.play();
                });
            } else {
                console.error("HLS.js is not supported in this browser.");
            }
        }

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        });
    </script>

</body>

</html>
