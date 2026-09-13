<?php
session_start();
require_once __DIR__ . '/../account_lifecycle_helper.php';

$hold = $_SESSION['account_hold'] ?? null;
if (!$hold || empty($hold['mode'])) {
    header('Location: login.php');
    exit();
}

$mode = (string)$hold['mode'];
$error = trim((string)($_GET['error'] ?? ''));
$notice = trim((string)($_GET['notice'] ?? ''));
$appealState = trim((string)($_GET['appeal'] ?? ''));
if ($appealState === '' && $notice !== '') {
    $appealState = stripos($notice, 'already') !== false ? 'pending' : 'sent';
}
$banReason = (string)($hold['ban_reason'] ?? 'Violation of community guidelines');
$deadlineTs = $mode === 'deletion' ? nexusDeletionDeadline($hold['deletion_requested_at'] ?? null) : null;
$deadlineIso = $deadlineTs ? date('c', $deadlineTs) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/components/head_boot.php'; ?>
    <title><?php echo $mode === 'banned' ? 'Account Banned' : 'Deletion Pending'; ?> - Nexus</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-[#050505] text-white font-sans antialiased" data-barba="wrapper">
    <?php include __DIR__ . '/components/page_loader.php'; ?>
    <div id="barba-container" class="relative overflow-hidden min-h-screen flex items-center justify-center" data-barba="container" data-barba-namespace="login"
         x-data="accountHoldPage()" x-init="init()">

        <a href="/index.php" class="fixed top-8 left-8 sm:top-12 sm:left-12 z-50 flex items-center justify-center w-14 h-14 rounded-full bg-black/40 border border-white/10 backdrop-blur-xl">
            <span class="material-symbols-outlined text-white/60 text-[20px]">arrow_back</span>
        </a>

        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-red-600 rounded-full blur-[160px] opacity-20"></div>
            <div class="absolute bottom-[-15%] right-[-10%] w-[500px] h-[500px] bg-indigo-600 rounded-full blur-[140px] opacity-30"></div>
        </div>

        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none flex items-center justify-center opacity-60 mask-radial">
            <div class="flex flex-row gap-6 transform -rotate-[15deg] scale-[1.5]" id="poster-wall-container"></div>
        </div>
        <script>
        (function() {
            var POSTER_IMAGES = [
                '3 Idiots.jpg','A Brighter Summer Day.jpg','Deadpool & Wolverine.jpg',
                'Deep Water.jpg','Doctor Strange in the Multiverse of Madness.jpg','Dune.jpg',
                'Forrest Gump.jpg','Grave of the Fireflies.jpg','Heartstopper Forever.jpg',
                'Inception.jpg','Interstellar.jpg','KPop Demon Hunters.jpg',
                'Minions & Monsters.jpg','Modern Times.jpg',"Now You See Me Now You Don't.jpg",
                'Obsession.jpg','Once We Were Us.jpg','Parasite.jpg',
                'Reservoir Dogs.jpg','Spider-Man Brand New Day.jpg','Supergirl.jpg',
                'Swapped.jpg','The Lady.jpg','The Mandalorian and Grogu.jpg',
                'The Mask.jpg','The Odyssey.jpg','The Salt of the Earth.jpg',
                'The Shawshank Redemption.jpg','Warfare.jpg','World War Z.jpg','Your Name.jpg'
            ];
            var BASE = 'Movies poster/';
            function shuffle(arr) {
                var a = arr.slice();
                for (var i = a.length - 1; i > 0; i--) {
                    var j = Math.floor(Math.random() * (i + 1));
                    var t = a[i]; a[i] = a[j]; a[j] = t;
                }
                return a;
            }
            var wall = document.getElementById('poster-wall-container');
            if (wall) {
                var html = '';
                for (var i = 0; i < 8; i++) {
                    var dir = i % 2 === 0 ? 'up' : 'down';
                    var dur = 120 + (i * 15);
                    var imgs = shuffle(POSTER_IMAGES).concat(shuffle(POSTER_IMAGES));
                    var posters = '';
                    imgs.forEach(function(f) {
                        posters += '<div class="poster"><img src="' + BASE + encodeURIComponent(f) + '" alt="" class="poster-img" loading="eager" decoding="async" onerror="this.parentElement.style.display=\'none\'"></div>';
                    });
                    html += '<div class="poster-col ' + dir + '" style="animation-duration:' + dur + 's">' + posters + '</div>';
                }
                wall.innerHTML = html;
            }
        })();
        </script>

        <main class="w-full max-w-md p-6 z-10">
            <a href="/index.php" class="text-center mb-10 block group">
                <h1 class="text-5xl font-black tracking-tighter uppercase italic flex items-center justify-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-tr from-red-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-[0_0_30px_rgba(220,38,38,0.4)]">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="nexus-text pr-2 group-hover:text-red-400 transition-colors">NEXUS</span>
                </h1>
                <p class="text-white/50 mt-3 text-sm font-light tracking-wide">Account status</p>
            </a>

            <div class="glass-card rounded-2xl p-8">
                <?php if ($error !== ''): ?>
                    <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs font-semibold"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($notice !== '' && $appealState === ''): ?>
                    <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold"><?php echo htmlspecialchars($notice); ?></div>
                <?php endif; ?>

                <?php if ($mode === 'deletion'): ?>
                    <div class="w-12 h-12 rounded-full bg-red-500/15 border border-red-500/30 flex items-center justify-center mx-auto mb-4 text-red-400">
                        <span class="material-symbols-outlined text-2xl">hourglass_top</span>
                    </div>
                    <h2 class="text-xl font-black text-center mb-2">Deletion pending</h2>
                    <p class="text-sm text-white/55 text-center mb-6">This account is scheduled for permanent deletion. Reverse it before the timer ends to keep everything.</p>
                    <div class="text-center mb-6">
                        <p class="text-[10px] uppercase tracking-widest text-white/40 mb-1">Time remaining</p>
                        <p class="text-3xl font-black tracking-tight text-red-300 mono" x-text="countdown">--:--:--</p>
                    </div>
                    <form action="../backend/cancel_deletion.php" method="POST">
                        <button type="submit" class="w-full bg-white text-black font-black uppercase tracking-widest text-xs py-4 rounded-xl hover:bg-emerald-500 hover:text-white transition-all">
                            Reverse deletion
                        </button>
                    </form>
                    <a href="login.php" class="block text-center mt-4 text-xs text-white/40 hover:text-white">Back to login</a>
                <?php else: ?>
                    <?php if (in_array($appealState, ['sent', 'pending'], true)): ?>
                        <div class="w-14 h-14 rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center mx-auto mb-4 text-emerald-400">
                            <span class="material-symbols-outlined text-3xl">check_circle</span>
                        </div>
                        <h2 class="text-xl font-black text-center mb-2"><?php echo $appealState === 'pending' ? 'Appeal already pending' : 'Appeal submitted'; ?></h2>
                        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-center">
                            <p class="text-sm text-emerald-200 font-semibold mb-2">
                                <?php echo $appealState === 'pending'
                                    ? 'Your appeal is already in the queue.'
                                    : 'Admins and moderators have been notified.'; ?>
                            </p>
                            <p class="text-xs text-white/55 leading-relaxed">It will appear in Reports as type Appeal. You can close this page and check back later.</p>
                        </div>
                        <a href="login.php" class="block w-full text-center bg-white text-black font-black uppercase tracking-widest text-xs py-4 rounded-xl hover:bg-emerald-500 hover:text-white transition-all">
                            Back to login
                        </a>
                    <?php else: ?>
                    <div class="w-12 h-12 rounded-full bg-red-500/15 border border-red-500/30 flex items-center justify-center mx-auto mb-4 text-red-400">
                        <span class="material-symbols-outlined text-2xl">gavel</span>
                    </div>
                    <h2 class="text-xl font-black text-center mb-2">Banned by admin</h2>
                    <p class="text-sm text-white/55 text-center mb-4">This account cannot sign in until an admin reviews an appeal.</p>
                    <div class="mb-6 p-4 rounded-xl bg-black/40 border border-white/10">
                        <p class="text-[10px] uppercase tracking-widest text-white/40 mb-1">Reason</p>
                        <p class="text-sm text-white/80"><?php echo htmlspecialchars($banReason); ?></p>
                    </div>
                    <form action="../backend/submit_ban_appeal.php" method="POST" class="space-y-4">
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-white/40">Appeal</label>
                        <textarea name="appeal_text" required rows="4" placeholder="Explain why this ban should be reviewed..."
                                  class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-red-500/50"></textarea>
                        <button type="submit" class="w-full bg-white text-black font-black uppercase tracking-widest text-xs py-4 rounded-xl hover:bg-red-600 hover:text-white transition-all">
                            Submit appeal
                        </button>
                    </form>
                    <a href="login.php" class="block text-center mt-4 text-xs text-white/40 hover:text-white">Back to login</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
    function accountHoldPage() {
        return {
            deadline: <?php echo json_encode($deadlineIso); ?>,
            countdown: '--:--:--',
            timer: null,
            init() {
                if (!this.deadline) return;
                this.tick();
                this.timer = setInterval(() => this.tick(), 1000);
            },
            tick() {
                const end = Date.parse(this.deadline);
                if (!end) return;
                const left = Math.max(0, end - Date.now());
                const h = Math.floor(left / 3600000);
                const m = Math.floor((left % 3600000) / 60000);
                const s = Math.floor((left % 60000) / 1000);
                this.countdown = [h, m, s].map((n) => String(n).padStart(2, '0')).join(':');
                if (left <= 0 && this.timer) {
                    clearInterval(this.timer);
                    this.countdown = '00:00:00';
                }
            }
        };
    }
    </script>
    <?php include __DIR__ . '/components/barba_scripts.php'; ?>
</body>
</html>
