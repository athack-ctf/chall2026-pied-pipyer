<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PyPI Manager</title>
    <style>
        /* CSS Reset & Variables */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --alien-yellow: #ffcc00;
            --alien-yellow-glow: rgba(255, 204, 0, 0.6);
            --alien-dark: #0a0a00;
            --alien-panel: rgba(15, 15, 5, 0.85);
            --text-main: #ffe680;
            --text-muted: #a69966;
        }

        body { 
            font-family: 'Courier New', Courier, monospace; 
            line-height: 1.6; 
            margin: 0; 
            background: radial-gradient(circle at center, #1a1a00 0%, #000000 100%); 
            background-attachment: fixed;
            color: var(--text-main); 
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Scanline Overlay */
        body::after {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            background-size: 100% 4px, 3px 100%;
            z-index: 999;
            pointer-events: none;
            opacity: 0.4;
        }
        
        /* Layout & HUD Wrapper */
        .page-wrapper { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 20px;
            position: relative; 
            z-index: 10; 
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        /* Typography */
        header { 
            background: transparent;
            color: var(--alien-yellow); 
            padding: 10px 0 20px 0;
            border-bottom: 2px solid var(--alien-yellow);
            box-shadow: 0 10px 20px -10px var(--alien-yellow-glow);
            text-transform: uppercase;
        }
        h1 { font-size: 2.5em; letter-spacing: 4px; text-shadow: 0 0 10px var(--alien-yellow); margin-bottom: 5px; }
        h2 { font-size: 1.3em; margin: 0 0 20px; color: #fff; background: var(--alien-yellow); display: inline-block; padding: 5px 15px; clip-path: polygon(10px 0, 100% 0, calc(100% - 10px) 100%, 0 100%); color: #000; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        h3 { color: var(--alien-yellow); margin-bottom: 15px; font-weight: normal; letter-spacing: 1px; font-size: 1em; border-left: 3px solid var(--alien-yellow); padding-left: 10px;}
        p { letter-spacing: 0.5px; }

        /* Alien Hex-Panels */
        .container { 
            background: var(--alien-panel); 
            padding: 30px; 
            position: relative;
            border: 1px solid rgba(255, 204, 0, 0.2);
            /* Chamfered corners for sci-fi look */
            clip-path: polygon(20px 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%, 0 20px);
            backdrop-filter: blur(10px);
            box-shadow: inset 0 0 30px rgba(0,0,0,0.8);
        }
        .container::before {
            content: ''; position: absolute; top: 0; left: 0; width: 40px; height: 2px; background: var(--alien-yellow);
        }
        
        /* Forms & Inputs */
        .forms { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: var(--text-muted); text-transform: uppercase; font-size: 0.85em; letter-spacing: 2px; }
        input[type="file"], input[type="url"], input[type="text"] { 
            width: 100%; 
            padding: 12px; 
            background: #000; 
            border: 1px solid var(--alien-yellow); 
            color: var(--alien-yellow); 
            outline: none;
            font-family: inherit;
            box-shadow: inset 0 0 10px rgba(255, 204, 0, 0.1);
        }
        input[type="file"]::file-selector-button {
            background: var(--alien-yellow); border: none; color: #000; padding: 6px 15px; cursor: pointer; margin-right: 15px; font-weight: bold; font-family: inherit; text-transform: uppercase; clip-path: polygon(10px 0, 100% 0, calc(100% - 10px) 100%, 0 100%);
        }
        input:focus { box-shadow: 0 0 15px var(--alien-yellow-glow), inset 0 0 10px rgba(255, 204, 0, 0.3); }
        
        /* Buttons */
        button { 
            background: transparent; 
            color: var(--alien-yellow); 
            border: 2px solid var(--alien-yellow); 
            padding: 10px 30px; 
            cursor: pointer; 
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-family: inherit;
            transition: all 0.2s ease;
            clip-path: polygon(10px 0, 100% 0, calc(100% - 10px) 100%, 0 100%);
        }
        button:hover { 
            background: var(--alien-yellow); 
            color: #000; 
            box-shadow: 0 0 20px var(--alien-yellow); 
        }
        
        /* Tables (Data Grid) */
        table { width: 100%; border-collapse: separate; border-spacing: 0 5px; margin-top: 15px; }
        th, td { padding: 15px; text-align: left; background: rgba(0,0,0,0.5); border-top: 1px solid transparent; border-bottom: 1px solid transparent; }
        th { color: var(--alien-yellow); font-size: 0.85em; letter-spacing: 2px; text-transform: uppercase; border-bottom: 1px solid var(--alien-yellow); background: #000; }
        tr:hover td { background: rgba(255, 204, 0, 0.1); border-top: 1px solid rgba(255, 204, 0, 0.3); border-bottom: 1px solid rgba(255, 204, 0, 0.3); color: #fff; cursor: crosshair; }
        
        /* Links & Inline Elements */
        a { color: var(--alien-yellow); text-decoration: none; position: relative; }
        a:hover { color: #fff; text-shadow: 0 0 8px var(--alien-yellow); }
        code { background: #000; padding: 4px 8px; border: 1px solid rgba(255,204,0,0.3); color: var(--alien-yellow); letter-spacing: 1px; }
        
        /* Messages & Warnings */
        .message { padding: 15px; margin: 15px 0; display: none; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; clip-path: polygon(15px 0, 100% 0, calc(100% - 15px) 100%, 0 100%); }
        .message.success { background: rgba(204, 255, 0, 0.1); border-left: 5px solid #ccff00; color: #ccff00; }
        .message.error { background: rgba(255, 51, 0, 0.1); border-left: 5px solid #ff3300; color: #ff3300; }
        
        .warning-box { background: repeating-linear-gradient(45deg, rgba(255,204,0,0.05), rgba(255,204,0,0.05) 10px, rgba(0,0,0,0.5) 10px, rgba(0,0,0,0.5) 20px); border: 1px solid var(--alien-yellow); color: var(--alien-yellow); padding: 20px; margin-bottom: 25px; box-shadow: 0 0 15px rgba(255, 204, 0, 0.1); text-align: center; clip-path: polygon(20px 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%, 0 20px); }
        .warning-box strong { display: block; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 3px; font-size: 1.2em; text-shadow: 0 0 8px var(--alien-yellow); }

        /* Terminal Output */
        #installOutput h3 { color: #fff; background: #333; display: inline-block; padding: 5px 15px; }
        #installOutput > div { border: 1px solid var(--alien-yellow) !important; background: #000 !important; box-shadow: inset 0 0 20px rgba(255, 204, 0, 0.1) !important; color: var(--alien-yellow) !important; padding: 20px !important; }
        #stdoutContent strong { color: #00ffcc !important; text-shadow: 0 0 5px #00ffcc; }
        #stderrContent strong { color: #ff3300 !important; text-shadow: 0 0 5px #ff3300; }
        #returnCode { border-top: 1px dashed rgba(255,204,0,0.5) !important; }

        /* Background Canvas */
        #bg-canvas { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 0; pointer-events: none; opacity: 0.3; }
    </style>
</head>
<body>
    <canvas id="bg-canvas"></canvas>
    
    <div class="page-wrapper">
        <header>
            <h1>🐍 PyPI Manager</h1>
            <p>Internal Python Package Repository // SYSTEM.ONLINE</p>
        </header>

        <div class="warning-box">
            <strong>⚠️ Signature Verification Required</strong>
            All packages must contain a signed RECORD file (RECORD.sig). Only packages signed with the repository's private key will be accepted.
        </div>

        <div class="container">
            <h2>Submit Package Artifacts</h2>
            
            <div class="forms">
                <div>
                    <h3>Upload File</h3>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Select signed .whl:</label>
                            <input type="file" name="file" accept=".whl" required>
                        </div>
                        <button type="submit">Upload</button>
                    </form>
                    <div id="uploadMsg" class="message"></div>
                </div>

                <div>
                    <h3>Import from URL</h3>
                    <form id="urlForm">
                        <div class="form-group">
                            <label>Package URL:</label>
                            <input type="url" name="url" placeholder="HTTPS://..." required>
                        </div>
                        <button type="submit">Import</button>
                    </form>
                    <div id="urlMsg" class="message"></div>
                </div>
            </div>

            <p style="color: var(--text-muted); border-top: 1px dashed rgba(255,204,0,0.3); padding-top: 15px; margin-top: 15px;">
               <strong>PEP 503 API:</strong> <code><a href="/simple/">/simple/</a></code> | 
               <strong>Manifest:</strong> <code><a href="/manifest/manifest.json">/manifest/manifest.json</a></code>
            </p>
        </div>

        <div class="container">
            <h2>Recent Files</h2>
            <?php if (empty($recentFiles)): ?>
                <p>No files yet.</p>
            <?php else: ?>
                <table>
                    <tr><th>Project</th><th>Version</th><th>Filename</th><th>Size</th><th>Accepted</th></tr>
                    <?php foreach ($recentFiles as $f): ?>
                        <tr>
                            <td><a href="/project/<?= htmlspecialchars($f['normalized_name']) ?>"><?= htmlspecialchars($f['normalized_name']) ?></a></td>
                            <td><?= htmlspecialchars($f['version']) ?></td>
                            <td><?= htmlspecialchars($f['filename']) ?></td>
                            <td><?= number_format($f['size_bytes'] / 1024, 1) ?> KB</td>
                            <td><?= date('Y-m-d H:i', strtotime($f['accepted_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="container">
            <h2>Install Package</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Install a hello-world package to the system Python environment to ensure CI operationality. (pip --version )</p>
            
            <form id="installForm">
                <div class="form-group">
                    </div>
                <button type="submit">Install Package</button>
            </form>

            <div id="installOutput" style="display: none; margin-top: 20px;">
                <h3>Installation Output</h3>
                <div>
                    <div id="stdoutContent" style="margin-bottom: 15px;">
                        <strong>STDOUT:</strong>
                        <pre id="stdout" style="margin: 5px 0; white-space: pre-wrap; word-wrap: break-word;"></pre>
                    </div>
                    <div id="stderrContent">
                        <strong>STDERR:</strong>
                        <pre id="stderr" style="margin: 5px 0; white-space: pre-wrap; word-wrap: break-word;"></pre>
                    </div>
                    <div id="returnCode" style="margin-top: 15px; padding-top: 15px;">
                        <strong style="color: #fff !important;">Return Code:</strong> <span id="returnCodeValue"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');
        let width, height;
        let particles = [];

        function resize() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 1.5;
                this.vy = (Math.random() - 0.5) * 1.5;
                this.radius = Math.random() * 2 + 1;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if (this.x < 0 || this.x > width) this.vx *= -1;
                if (this.y < 0 || this.y > height) this.vy *= -1;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = '#ffcc00';
                ctx.fill();
                ctx.shadowBlur = 10;
                ctx.shadowColor = '#ffcc00';
            }
        }

        for (let i = 0; i < 80; i++) particles.push(new Particle());

        function animate() {
            ctx.clearRect(0, 0, width, height);
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 150) {
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(255, 204, 0, ${0.3 - dist/500})`;
                        ctx.lineWidth = 1.5;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(animate);
        }
        animate();
    </script>

    <script>
        document.getElementById('uploadForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('uploadMsg');
            try {
                const res = await fetch('/ingest/upload', { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                if (data.success) {
                    msg.className = 'message success';
                    msg.textContent = `Success: ${data.data.project} v${data.data.version}`;
                    msg.style.display = 'block';
                    setTimeout(() => location.reload(), 2000);
                } else throw new Error(data.error);
            } catch (err) {
                msg.className = 'message error';
                msg.textContent = 'Error: ' + err.message;
                msg.style.display = 'block';
            }
        };

        document.getElementById('urlForm').onsubmit = async (e) => {
            e.preventDefault();
            const msg = document.getElementById('urlMsg');
            const url = e.target.url.value;
            try {
                const res = await fetch('/ingest/url', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url })
                });
                const data = await res.json();
                if (data.success) {
                    msg.className = 'message success';
                    msg.textContent = `Success: ${data.data.project} v${data.data.version}`;
                    msg.style.display = 'block';
                    setTimeout(() => location.reload(), 2000);
                } else throw new Error(data.error);
            } catch (err) {
                msg.className = 'message error';
                msg.textContent = 'Error: ' + err.message;
                msg.style.display = 'block';
            }
        };

        document.getElementById('installForm').onsubmit = async (e) => {
            e.preventDefault();
            const outputDiv = document.getElementById('installOutput');
            const stdoutEl = document.getElementById('stdout');
            const stderrEl = document.getElementById('stderr');
            const returnCodeEl = document.getElementById('returnCodeValue');

            outputDiv.style.display = 'block';
            stdoutEl.textContent = 'Installing... Please wait...';
            stderrEl.textContent = '';
            returnCodeEl.textContent = '';

            try {
                const res = await fetch('/pip/install', {
                    method: 'GET'
                });
                const data = await res.json();

                if (data.success) {
                    stdoutEl.textContent = data.stdout || '(no output)';
                    stderrEl.textContent = data.stderr || '(no output)';
                    returnCodeEl.textContent = data.return_code;
                    returnCodeEl.style.color = data.return_code === 0 ? '#00ffcc' : '#ff3300';
                } else {
                    stdoutEl.textContent = data.stdout || '(no output)';
                    stderrEl.textContent = data.stderr || data.error;
                    returnCodeEl.textContent = data.return_code || 'N/A';
                    returnCodeEl.style.color = '#ff3300';
                }
            } catch (err) {
                stdoutEl.textContent = '';
                stderrEl.textContent = 'Request failed: ' + err.message;
                returnCodeEl.textContent = 'N/A';
                returnCodeEl.style.color = '#ff3300';
            }
        };
    </script>
</body>
</html>