<?php
header('Content-Type: application/json');
session_start();

$userId = (int)($_SESSION['user_id'] ?? 0);
$userName = trim((string)($_SESSION['user_name'] ?? ''));
$userRole = strtolower((string)($_SESSION['user_role'] ?? ''));
$isGuest = $userRole === 'guest' || $userId <= 0;
$firstName = $userName !== '' ? explode(' ', $userName)[0] : ($isGuest ? 'there' : 'there');
session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'reply' => 'Send me a message to get started.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) {
    $payload = [];
}

$message = trim((string)($payload['message'] ?? $_POST['message'] ?? ''));
$context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

if ($message === '') {
    echo json_encode([
        'success' => true,
        'reply' => "Hey {$firstName}. Ask me for a movie, a mood pick, or how Nexus rooms work.",
        'movies' => [],
        'suggestions' => ['Recommend a movie', 'I feel sad', 'What is Nexus?'],
    ]);
    exit;
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../poster_helper.php';
require_once __DIR__ . '/../premium_status_helper.php';

$q = normalizeChatQuery($message);
$result = handleChatIntent($conn, $q, $message, $context, [
    'user_id' => $userId,
    'first_name' => $firstName,
    'is_guest' => $isGuest,
]);

echo json_encode($result);

function handleChatIntent(PDO $conn, string $q, string $original, array $context, array $user): array
{
    if (preg_match('/\b(thanks|thank you|thx|ty)\b/', $q)) {
        return packReply("You got it, {$user['first_name']}. Want another pick or a walkthrough?", [], [
            'Recommend a movie', 'How do missions work?', 'How do I host?'
        ], 'thanks');
    }

    if (preg_match('/\b(hi|hello|hey|yo|sup|what is up|what\'?s up|whats up|good morning|good evening|good afternoon)\b/', $q)
        && !preg_match('/\b(movie|film|watch|host|room|premium|mission|nexus)\b/', $q)
    ) {
        return packReply(
            "Hey {$user['first_name']} — I'm Nex. I can recommend a film from the catalog, match one to your mood or the weather, or walk you through rooms, missions, and Premium.",
            [],
            ['Recommend a movie', "It's raining", 'I feel happy', 'What is Nexus?'],
            'greet'
        );
    }

    if (isIdentityAsk($q)) {
        return packReply(
            "I'm Nex, the Nexus guide. I live in this chat — not a person on the other end, just here to pick movies and walk you through rooms, missions, Premium, and the rest of the app.\n\nAsk me casually. “It’s raining,” “my crush rejected me,” “how do I host,” all work.",
            [],
            ['Recommend a movie', 'My crush rejected me', 'What is Nexus?', 'How do I host?'],
            'identity'
        );
    }

    if (isHowAreYou($q)) {
        return packReply(
            "Doing alright — queued up and ready. How are you feeling? I can match a title to that, or just help you host a room.",
            [],
            ['I feel happy', 'I feel sad', 'Recommend a movie', 'How do I host?'],
            'smalltalk'
        );
    }

    if (isHeartbreakAsk($q)) {
        $movies = recommendMovies($conn, $user['user_id'], [
            'genres' => ['Drama', 'Romance'],
            'limit' => 3,
        ]);
        $comfort = "That's a rough one, {$user['first_name']}. You don't have to unpack it here — but a movie can sit with that feeling or take the edge off. Here are a few that get it:";
        if (!$movies) {
            return packReply(
                "That's a rough one, {$user['first_name']}. Want something that sits with that feeling, or something lighter to get your mind off it?",
                [],
                ['Something sad', 'Something funny', 'Recommend a movie'],
                'comfort',
                ['Drama', 'Romance']
            );
        }
        return movieReply($movies, $comfort, ['Drama', 'Romance']);
    }

    if (isFollowUp($q) && !empty($context['intent']) && $context['intent'] === 'movie_recommend') {
        $genres = array_values(array_filter((array)($context['genres'] ?? [])));
        $movies = recommendMovies($conn, $user['user_id'], [
            'genres' => $genres,
            'exclude_ids' => array_map('intval', (array)($context['movie_ids'] ?? [])),
            'limit' => 3,
        ]);
        return movieReply($movies, $genres ? 'Here are a few more in that lane.' : 'Here are a few more from the catalog.', $genres);
    }

    if (preg_match('/\b(what is nexus|what\'s nexus|whats nexus|about nexus)\b/', $q)
        || (preg_match('/\bnexus\b/', $q) && preg_match('/\b(what|who|about|explain)\b/', $q) && !isIdentityAsk($q))
    ) {
        return packReply(
            "Nexus is a watch-party app. You pick a title from the catalog, open a private room, and everyone stays on the same playhead — pause, seek, chat, and video in one place.\n\nGuests can join a room from a link. Signed-in members get watchlists, friends, missions, the shop, and hosting controls.",
            [],
            ['How do I host?', 'Recommend a movie', 'What is Premium?'],
            'nexus'
        );
    }

    if (preg_match('/\b(host|create|start|open)\b.*\b(party|room|watch party)\b/', $q)
        || preg_match('/\bhow (do i|to) host\b/', $q)
        || preg_match('/\bhosting\b/', $q)
    ) {
        $hostText = $user['is_guest']
            ? "Hosting needs a full account. Tap Login, then use Host Party in the bottom-right. Pick a title (or let me recommend one), create the room, and share the invite code or link.\n\nAs host you control play, can mute or kick, and can rotate the invite."
            : "Tap Host Party in the bottom-right, pick a title, and Nexus creates a private room with a short invite code. Share that link — friends land in the same lobby.\n\nYou control play, mute, kick, and chat. If someone drifts, the room pulls them back onto your timeline.";
        return packReply($hostText, [], ['Recommend something to host', 'How do rooms work?', 'What is Premium?'], 'host');
    }

    if (preg_match('/\b(room|lobby|invite code|join a room|watch party)\b/', $q)
        && !preg_match('/\b(movie|film|recommend)\b/', $q)
    ) {
        return packReply(
            "A Nexus room is one movie, one playhead, live chat, and optional video.\n\n- Host creates it and picks the title\n- Everyone joins with the invite link or 6-character code\n- The host can mute, kick, lock play, and rotate the invite\n- Rooms are invite-only — chat stays in the room\n\nFrom the dashboard you can also join a friend's live room if they invite you.",
            [],
            ['How do I host?', 'Recommend a movie', 'What is Premium?'],
            'rooms'
        );
    }

    if (preg_match('/\b(premium|upgrade|subscribe|subscription|plan|4\.99)\b/', $q)) {
        $extra = '';
        if ($user['user_id'] > 0 && !$user['is_guest']) {
            try {
                $premium = resolveUserPremium($conn, $user['user_id']);
                if (!empty($premium['is_premium'])) {
                    $until = $premium['premium_expires_at'] ?? '';
                    $extra = $until
                        ? "\n\nYour Premium is on through {$until}. You can manage it from the Premium tab."
                        : "\n\nYou already have Premium active.";
                } else {
                    $extra = "\n\nYou are on Free right now. Open the Premium tab to unlock.";
                }
            } catch (Throwable $e) {
                $extra = '';
            }
        }
        return packReply(
            "Premium is \$4.99 a month. You keep every Free feature, plus exclusive profile cosmetics and borders, unlimited hosting, a Premium badge, and no protocol caps." . $extra,
            [],
            ['How do I host?', 'How do missions work?', 'Recommend a movie'],
            'premium'
        );
    }

    if (preg_match('/\b(mission|missions|quest|quests|daily|weekly|monthly|points?)\b/', $q)
        && !preg_match('/\b(movie|film|watch)\b/', $q)
    ) {
        $missionLines = liveMissionBlurb($conn, $user);
        return packReply(
            "Missions are repeating tasks that pay out points.\n\n- Daily / weekly / monthly cycles reset on their own\n- Each mission has a target (watch, rate, host, etc.)\n- When the bar fills, tap Claim to add the points\n- Spend points in the Shop for borders and cosmetics\n\nOpen the quests drawer from the dashboard (the trophy panel)." . $missionLines,
            [],
            ['What is Premium?', 'Where is the shop?', 'Recommend a movie'],
            'missions'
        );
    }

    if (preg_match('/\b(shop|store|inventory|border|cosmetic|theme)\b/', $q)) {
        return packReply(
            "The Point Shop is the Shop tab. Earn points from missions, then buy profile borders, themes, and other cosmetics. Equip them from Account. Guests can browse, but claiming missions and buying items needs a full account.",
            [],
            ['How do missions work?', 'What is Premium?', 'Recommend a movie'],
            'shop'
        );
    }

    if (preg_match('/\b(friend|friends|invite|dm|message|chat)\b/', $q)
        && !preg_match('/\b(room chat|in the room)\b/', $q)
    ) {
        return packReply(
            "Friends live in the header people icon. Search a username, send a request, then DM them or invite them into a room. Online friends show a live status. Room chat is separate — that one stays inside the watch party.",
            [],
            ['How do I host?', 'Recommend a movie', 'What is Nexus?'],
            'friends'
        );
    }

    if (preg_match('/\b(account|profile|avatar|password|settings?)\b/', $q)) {
        return packReply(
            "Account is the Account tab — username, email, password, and avatar. The header profile menu is the shortcut. Borders you buy in the Shop equip there too. Guests need to register to keep a profile between sessions.",
            [],
            ['What is Premium?', 'How do missions work?', 'Recommend a movie'],
            'account'
        );
    }

    if (preg_match('/\b(help|guide|how do i|what can you|commands?)\b/', $q)
        && !preg_match('/\b(host|room|movie|mission|premium)\b/', $q)
    ) {
        return packReply(
            "I can:\n- Recommend movies by title, genre, mood, or weather\n- Explain Nexus, rooms, and hosting\n- Cover Premium, missions, shop, friends, and your account\n\nTry “something cozy for a rainy night” or “how do I host a party?”",
            [],
            ['Recommend a movie', "It's raining", 'I feel sad', 'How do I host?'],
            'help'
        );
    }

    $weather = detectWeather($q);
    $mood = detectMood($q);
    $genres = detectGenres($q);
    $titleHint = detectTitleHint($q, $conn);
    $wantsMore = isFollowUp($q);
    $wantsMovie = preg_match('/\b(movie|movies|film|films|watch|recommend|suggestion|suggest|catalog|something to watch|what should i watch|play)\b/', $q)
        || $weather !== '' || $mood !== '' || $genres || $titleHint !== '';

    if ($wantsMovie || $wantsMore) {
        $filterGenres = $genres;
        if ($weather !== '') {
            $filterGenres = array_values(array_unique(array_merge($filterGenres, weatherGenres($weather))));
        }
        if ($mood !== '') {
            $filterGenres = array_values(array_unique(array_merge($filterGenres, moodGenres($mood))));
        }
        if ($titleHint !== '' && !$filterGenres) {
            $filterGenres = genresForTitle($conn, $titleHint);
        }

        $movies = recommendMovies($conn, $user['user_id'], [
            'genres' => $filterGenres,
            'title' => $titleHint,
            'trending' => (bool)preg_match('/\b(trend|popular|most viewed|hot)\b/', $q),
            'limit' => 3,
        ]);

        $lead = movieLeadIn($weather, $mood, $filterGenres, $titleHint, $movies);
        return movieReply($movies, $lead, $filterGenres);
    }

    return packReply(
        "I didn't quite catch that, {$user['first_name']}. Try it like you would text a friend — “what are you,” “I’m sad,” “my crush rejected me,” or “something cozy for rain.” I can also walk through hosting, rooms, missions, and Premium.",
        [],
        ['What are you?', 'My crush rejected me', "It's raining", 'How do I host?'],
        'fallback'
    );
}

function normalizeChatQuery(string $message): string
{
    $q = function_exists('mb_strtolower') ? mb_strtolower($message) : strtolower($message);
    $q = str_replace(['’', '‘', '`'], "'", $q);
    $contractions = [
        "what's" => 'what is',
        "who're" => 'who are',
        "you're" => 'you are',
        "i'm" => 'i am',
        "i've" => 'i have',
        "don't" => 'do not',
        "didn't" => 'did not',
        "can't" => 'cannot',
        "won't" => 'will not',
    ];
    $q = strtr($q, $contractions);
    $words = [
        'whats' => 'what is',
        'whos' => 'who is',
        'youre' => 'you are',
        'dont' => 'do not',
        'didnt' => 'did not',
        'cant' => 'cannot',
        'wru' => 'who are you',
        'hru' => 'how are you',
        'idk' => 'i do not know',
        'rn' => 'right now',
        'u' => 'you',
        'ur' => 'your',
        'r' => 'are',
        'im' => 'i am',
        'sup' => 'hey',
    ];
    $q = preg_replace_callback('/\b(' . implode('|', array_keys($words)) . ')\b/', static function ($m) use ($words) {
        return $words[$m[1]] ?? $m[0];
    }, $q) ?? $q;
    $q = preg_replace("/[^\p{L}\p{N}'\s]+/u", ' ', $q) ?? $q;
    $q = preg_replace('/\s+/', ' ', $q) ?? $q;
    return trim($q);
}

function isIdentityAsk(string $q): bool
{
    return (bool)(
        preg_match('/\b(what|who)\s+(are|is)\s+(you|this|nex)\b/', $q)
        || preg_match('/\b(are you|is this)\s+(a\s+)?(bot|ai|robot|assistant|chatbot|nex|human|person|real)\b/', $q)
        || preg_match('/\b(introduce yourself|your name|who is nex|what is nex)\b/', $q)
        || preg_match('/\btell me about yourself\b/', $q)
    );
}

function isHowAreYou(string $q): bool
{
    return (bool)preg_match('/\b(how are you|how you doing|how do you feel|you good|you okay)\b/', $q)
        && !preg_match('/\b(movie|film|host|room|premium|mission)\b/', $q);
}

function isHeartbreakAsk(string $q): bool
{
    return (bool)(
        preg_match('/\b(crush|dumped|dump me|rejected|rejection|friendzone|friendzoned|ghosted|broke up|break ?up|heartbreak|heartbroken|heart broken)\b/', $q)
        || preg_match('/\b(she|he|they)\s+(left me|hate[s]? me|do not love|said no)\b/', $q)
        || preg_match('/\b(got|was|been)\s+(rejected|dumped|friendzoned|ghosted)\b/', $q)
        || preg_match('/\b(miss (her|him|them)|do not love me|nobody likes me)\b/', $q)
    );
}

function packReply(string $reply, array $movies, array $suggestions, string $intent, array $genres = []): array
{
    return [
        'success' => true,
        'reply' => $reply,
        'movies' => $movies,
        'suggestions' => $suggestions,
        'intent' => $intent,
        'genres' => $genres,
    ];
}

function movieReply(array $movies, string $lead, array $genres): array
{
    if (!$movies) {
        return packReply(
            $lead . " I couldn't find a match in the catalog right now. Try a genre like action, drama, or comedy.",
            [],
            ['Trending movies', 'I feel happy', 'How do I host?'],
            'movie_recommend',
            $genres
        );
    }
    return packReply($lead, $movies, ['More like this', 'How do I host this?', 'What is Premium?'], 'movie_recommend', $genres);
}

function isFollowUp(string $q): bool
{
    return (bool)preg_match('/\b(more|another|else|different|others?|again|next)\b/', $q)
        && !preg_match('/\b(premium|mission|host|room|nexus|account)\b/', $q);
}

function detectWeather(string $q): string
{
    if (preg_match('/\b(rain|rainy|raining|storm|stormy|thunder|drizzle)\b/', $q)) return 'rain';
    if (preg_match('/\b(snow|snowy|cold|freezing|winter|blizzard)\b/', $q)) return 'snow';
    if (preg_match('/\b(sun|sunny|hot|heat|summer|bright)\b/', $q)) return 'sun';
    if (preg_match('/\b(cloud|cloudy|fog|foggy|overcast|grey|gray)\b/', $q)) return 'cloud';
    if (preg_match('/\b(wind|windy)\b/', $q)) return 'wind';
    return '';
}

function detectMood(string $q): string
{
    if (preg_match('/\b(sad|down|heartbreak|heartbroken|heart broken|cry|crying|depressed|lonely|empty|rejected|dumped|crush|break ?up|ghosted|friendzone)\b/', $q)) return 'sad';
    if (preg_match('/\b(happy|glad|cheerful|good mood|excited|hype|hyped|great|awesome|pumped)\b/', $q)) return 'happy';
    if (preg_match('/\b(scare|scared|spooky|horror|creepy)\b/', $q)) return 'scared';
    if (preg_match('/\b(romantic|romance|date night|in love|lovey)\b/', $q)) return 'romantic';
    if (preg_match('/\b(bored|boring|nothing to do)\b/', $q)) return 'bored';
    if (preg_match('/\b(stress|stressed|anxious|anxiety|overwhelmed)\b/', $q)) return 'stressed';
    if (preg_match('/\b(nostalgic|nostalgia|throwback)\b/', $q)) return 'nostalgic';
    if (preg_match('/\b(angry|mad|furious|rage)\b/', $q)) return 'angry';
    if (preg_match('/\b(tired|sleepy|exhausted|chill|cozy|relax)\b/', $q)) return 'tired';
    return '';
}

function detectGenres(string $q): array
{
    $map = [
        'action' => 'Action',
        'comedy' => 'Comedy',
        'funny' => 'Comedy',
        'drama' => 'Drama',
        'horror' => 'Horror',
        'thriller' => 'Thriller',
        'romance' => 'Romance',
        'romantic' => 'Romance',
        'sci-fi' => 'Sci-Fi',
        'scifi' => 'Sci-Fi',
        'science fiction' => 'Sci-Fi',
        'animation' => 'Animation',
        'anime' => 'Animation',
        'adventure' => 'Adventure',
        'crime' => 'Crime',
        'mystery' => 'Mystery',
        'documentary' => 'Documentary',
        'family' => 'Family',
        'fantasy' => 'Fantasy',
    ];
    $found = [];
    foreach ($map as $needle => $genre) {
        if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $q)) {
            $found[] = $genre;
        }
    }
    return array_values(array_unique($found));
}

function weatherGenres(string $weather): array
{
    switch ($weather) {
        case 'rain':
            return ['Drama', 'Romance', 'Thriller', 'Mystery'];
        case 'snow':
            return ['Drama', 'Animation', 'Romance', 'Family'];
        case 'sun':
            return ['Comedy', 'Action', 'Adventure'];
        case 'cloud':
            return ['Mystery', 'Thriller', 'Horror', 'Drama'];
        case 'wind':
            return ['Action', 'Adventure'];
        default:
            return [];
    }
}

function moodGenres(string $mood): array
{
    switch ($mood) {
        case 'sad':
            return ['Drama', 'Romance'];
        case 'happy':
            return ['Comedy', 'Action', 'Adventure'];
        case 'scared':
            return ['Horror', 'Thriller'];
        case 'romantic':
            return ['Romance', 'Drama'];
        case 'bored':
            return ['Action', 'Comedy', 'Adventure'];
        case 'stressed':
            return ['Comedy', 'Animation'];
        case 'nostalgic':
            return ['Drama', 'Animation'];
        case 'angry':
            return ['Action', 'Thriller'];
        case 'tired':
            return ['Animation', 'Comedy', 'Drama'];
        default:
            return [];
    }
}

function detectTitleHint(string $q, PDO $conn): string
{
    if (preg_match('/\b(?:like|similar to|same as)\s+["\']?(.+?)["\']?$/', $q, $m)) {
        return trim($m[1], " .!?");
    }
    try {
        $titles = $conn->query("SELECT title FROM movies")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $best = '';
        $bestLen = 0;
        foreach ($titles as $title) {
            $t = function_exists('mb_strtolower') ? mb_strtolower((string)$title) : strtolower((string)$title);
            $len = function_exists('mb_strlen') ? mb_strlen($t) : strlen($t);
            $pos = function_exists('mb_strpos') ? mb_strpos($q, $t) : strpos($q, $t);
            if ($t !== '' && $pos !== false && $len > $bestLen) {
                $best = (string)$title;
                $bestLen = $len;
            }
        }
        return $best;
    } catch (Throwable $e) {
        return '';
    }
}

function genresForTitle(PDO $conn, string $title): array
{
    try {
        $stmt = $conn->prepare("
            SELECT g.genre_name
            FROM movies m
            JOIN movie_and_genres mg ON mg.movie_id = m.movie_id
            JOIN genres g ON g.genre_id = mg.genre_id
            WHERE m.title LIKE ?
            LIMIT 8
        ");
        $stmt->execute(['%' . $title . '%']);
        return array_values(array_unique(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    } catch (Throwable $e) {
        return [];
    }
}

function recommendMovies(PDO $conn, int $userId, array $opts): array
{
    $genres = array_values(array_filter((array)($opts['genres'] ?? [])));
    $title = trim((string)($opts['title'] ?? ''));
    $exclude = array_values(array_filter(array_map('intval', (array)($opts['exclude_ids'] ?? []))));
    $trending = !empty($opts['trending']);
    $limit = max(1, min(5, (int)($opts['limit'] ?? 3)));

    $sql = "
        SELECT
            m.movie_id AS id,
            m.title,
            m.description,
            m.duration,
            m.view_count,
            COALESCE((
                SELECT ROUND(AVG(r.rating), 1)
                FROM movie_rating r
                WHERE r.movie_id = m.movie_id
            ), 0.0) AS rating,
            COALESCE((
                SELECT GROUP_CONCAT(g.genre_name SEPARATOR ', ')
                FROM movie_and_genres mg
                JOIN genres g ON g.genre_id = mg.genre_id
                WHERE mg.movie_id = m.movie_id
            ), '') AS genres
        FROM movies m
    ";
    $where = [];
    $params = [];

    if ($genres) {
        $likes = [];
        foreach ($genres as $g) {
            $likes[] = 'g.genre_name LIKE ?';
            $params[] = '%' . $g . '%';
        }
        $where[] = 'EXISTS (
            SELECT 1 FROM movie_and_genres mg
            JOIN genres g ON g.genre_id = mg.genre_id
            WHERE mg.movie_id = m.movie_id AND (' . implode(' OR ', $likes) . ')
        )';
    }
    if ($title !== '') {
        $where[] = 'm.title LIKE ?';
        $params[] = '%' . $title . '%';
    }
    if ($exclude) {
        $placeholders = implode(',', array_fill(0, count($exclude), '?'));
        $where[] = "m.movie_id NOT IN ({$placeholders})";
        foreach ($exclude as $id) {
            $params[] = $id;
        }
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= $trending
        ? ' ORDER BY m.view_count DESC, rating DESC, m.title ASC'
        : ' ORDER BY rating DESC, m.view_count DESC, m.title ASC';
    $sql .= ' LIMIT ' . (int)$limit;

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('chatbot recommend: ' . $e->getMessage());
        $rows = [];
    }

    if (!$rows && ($genres || $title !== '')) {
        return recommendMovies($conn, $userId, ['trending' => true, 'exclude_ids' => $exclude, 'limit' => $limit]);
    }

    $out = [];
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $out[] = [
            'id' => $id,
            'movie_id' => $id,
            'title' => (string)$row['title'],
            'description' => trim((string)($row['description'] ?? '')),
            'duration' => (int)($row['duration'] ?? 0),
            'view_count' => (int)($row['view_count'] ?? 0),
            'rating' => number_format((float)$row['rating'], 1),
            'genres' => $row['genres'] ? explode(', ', (string)$row['genres']) : [],
            'img' => moviePosterUrl($id),
            'cover_image' => moviePosterUrl($id),
        ];
    }
    return $out;
}

function movieLeadIn(string $weather, string $mood, array $genres, string $titleHint, array $movies): string
{
    if (!$movies) {
        return 'Nothing matched that ask.';
    }
    if ($weather === 'rain') {
        return 'Rainy-night energy. These sit well with weather like that:';
    }
    if ($weather === 'snow') {
        return 'Cold-weather picks from the catalog:';
    }
    if ($weather === 'sun') {
        return 'Bright-day picks — lighter or louder:';
    }
    if ($weather === 'cloud') {
        return 'Overcast mood. A little mystery, a little weight:';
    }
    if ($mood === 'sad') {
        return 'When you want something that gets it:';
    }
    if ($mood === 'happy') {
        return 'Good-mood titles from the catalog:';
    }
    if ($mood === 'scared') {
        return 'If you want a jolt:';
    }
    if ($mood === 'romantic') {
        return 'Date-night energy:';
    }
    if ($mood === 'bored') {
        return 'These should wake the room up:';
    }
    if ($mood === 'tired') {
        return 'Easy watches when you just want to sink in:';
    }
    if ($titleHint !== '') {
        return 'If you are thinking “' . $titleHint . '”, start here:';
    }
    if ($genres) {
        return 'From the catalog in ' . implode(', ', $genres) . ':';
    }
    return 'Here is what I would put on:';
}

function liveMissionBlurb(PDO $conn, array $user): string
{
    if ($user['is_guest'] || $user['user_id'] <= 0) {
        return "\n\nMissions unlock after you create a full account.";
    }
    try {
        $stmt = $conn->prepare("
            SELECT m.title, m.points_reward, m.reset_cycle, m.target_count,
                   COALESCE(um.progress, 0) AS progress,
                   COALESCE(um.done_status, 0) AS done
            FROM missions m
            LEFT JOIN user_missions um ON um.mission_id = m.mission_id AND um.user_id = ?
            WHERE m.is_active = 1
            ORDER BY m.reset_cycle ASC, m.mission_id ASC
            LIMIT 4
        ");
        $stmt->execute([$user['user_id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) {
            return '';
        }
        $bits = [];
        foreach ($rows as $row) {
            $cycle = ucfirst((string)$row['reset_cycle']);
            $bits[] = "- {$cycle}: {$row['title']} ({$row['progress']}/{$row['target_count']}) — {$row['points_reward']} pts";
        }
        return "\n\nYour live missions:\n" . implode("\n", $bits);
    } catch (Throwable $e) {
        return '';
    }
}
