<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($project['display_name']) ?></title>
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
        h1 { font-size: 2.5em; letter-spacing: 4px; text-shadow: 0 0 10px var(--alien-yellow); margin-bottom: 5px; margin-top: 10px;}
        h2 { font-size: 1.3em; margin: 0 0 20px; color: #fff; background: var(--alien-yellow); display: inline-block; padding: 5px 15px; clip-path: polygon(10px 0, 100% 0, calc(100% - 10px) 100%, 0 100%); color: #000; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        
        /* Breadcrumb (Console Path) */
        .breadcrumb { margin-bottom: 10px; color: var(--text-muted); font-weight: bold; text-transform: uppercase; letter-spacing: 2px; font-size: 0.9em; }
        .breadcrumb a { color: var(--alien-yellow); text-decoration: none; border: 1px solid var(--alien-yellow); padding: 2px 8px; clip-path: polygon(5px 0, 100% 0, calc(100% - 5px) 100%, 0 100%); transition: 0.2s; }
        .breadcrumb a:hover { background: var(--alien-yellow); color: #000; box-shadow: 0 0 10px var(--alien-yellow); }
        
        /* Alien Hex-Panels */
        .container { 
            background: var(--alien-panel); 
            padding: 30px; 
            position: relative;
            border: 1px solid rgba(255, 204, 0, 0.2);
            clip-path: polygon(20px 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%, 0 20px);
            /* backdrop-filter: blur(10px); */
            box-shadow: inset 0 0 30px rgba(0,0,0,0.8);
        }
        .container::before {
            content: ''; position: absolute; top: 0; left: 0; width: 40px; height: 2px; background: var(--alien-yellow);
        }
        
        /* Tables (Data Grid) */
        table { width: 100%; border-collapse: separate; border-spacing: 0 5px; margin-top: 15px; }
        th, td { padding: 15px; text-align: left; background: rgba(0,0,0,0.5); border-top: 1px solid transparent; border-bottom: 1px solid transparent; }
        th { color: var(--alien-yellow); font-size: 0.85em; letter-spacing: 2px; text-transform: uppercase; border-bottom: 1px solid var(--alien-yellow); background: #000; }
        tr:hover td { background: rgba(255, 204, 0, 0.1); border-top: 1px solid rgba(255, 204, 0, 0.3); border-bottom: 1px solid rgba(255, 204, 0, 0.3); color: #fff; cursor: crosshair; }
        
        /* Links & Inline Elements */
        a { color: var(--alien-yellow); text-decoration: none; position: relative; }
        a:hover { color: #fff; text-shadow: 0 0 8px var(--alien-yellow); }
        code { background: #000; padding: 4px 8px; border: 1px solid rgba(255,204,0,0.3); color: var(--alien-yellow); letter-spacing: 1px; font-size: 13px; }
        
        /* Badges */
        .badge { display: inline-block; padding: 4px 12px; background: rgba(0, 255, 204, 0.1); color: #00ffcc; border: 1px solid #00ffcc; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold; box-shadow: 0 0 8px rgba(0, 255, 204, 0.3); clip-path: polygon(5px 0, 100% 0, calc(100% - 5px) 100%, 0 100%); }
        .badge.unverified { background: rgba(255, 51, 0, 0.1); color: #ff3300; border: 1px solid #ff3300; box-shadow: 0 0 8px rgba(255, 51, 0, 0.3); }

        /* Action Buttons */
        .action-btn { border: 1px solid var(--alien-yellow); padding: 6px 15px; color: var(--alien-yellow); text-transform: uppercase; font-size: 12px; letter-spacing: 2px; font-weight: bold; clip-path: polygon(8px 0, 100% 0, calc(100% - 8px) 100%, 0 100%); display: inline-block; transition: 0.2s; }
        .action-btn:hover { background: var(--alien-yellow); color: #000 !important; box-shadow: 0 0 10px var(--alien-yellow); }

        /* Background Canvas */
        #bg-canvas { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 0; pointer-events: none; opacity: 0.3; }
    </style>
</head>
<body>
    <canvas id="bg-canvas"></canvas>

    <div class="page-wrapper">
        <header>
            <div class="breadcrumb"><a href="/">[DIR] ROOT</a></div>
            <h1><?= htmlspecialchars($project['display_name']) ?></h1>
        </header>

        <div class="container">
            <h2>Project Info</h2>
            <p style="margin-bottom: 15px;"><strong>Normalized:</strong> <code><?= htmlspecialchars($project['normalized_name']) ?></code></p>
            <p style="margin-bottom: 15px;"><strong>Created:</strong> <span style="color: var(--text-muted);"><?= htmlspecialchars($project['created_at']) ?></span></p>
            <p><strong>PEP 503:</strong> <a href="/simple/<?= htmlspecialchars($project['normalized_name']) ?>/">/simple/<?= htmlspecialchars($project['normalized_name']) ?>/</a></p>
        </div>

        <div class="container">
            <h2>Files (<?= count($project['files'] ?? []) ?>)</h2>
            <?php if (empty($project['files'])): ?>
                <p style="color: var(--text-muted);">No files.</p>
            <?php else: ?>
                <table>
                    <tr><th>Filename</th><th>Version</th><th>Size</th><th>SHA256</th><th>Metadata</th><th>Actions</th></tr>
                    <?php foreach ($project['files'] as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['filename']) ?></td>
                            <td><?= htmlspecialchars($f['version']) ?></td>
                            <td><?= number_format($f['size_bytes'] / 1024, 1) ?> KB</td>
                            <td><code><?= substr($f['sha256'], 0, 16) ?>...</code></td>
                            <td>
                                <?php if ($f['metadata_verified']): ?>
                                    <span class="badge">Verified</span>
                                <?php else: ?>
                                    <span class="badge unverified">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/packages/<?= urlencode($project['normalized_name']) ?>/<?= urlencode($f['version']) ?>/<?= urlencode($f['filename']) ?>" class="action-btn">Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
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
</body>
</html>