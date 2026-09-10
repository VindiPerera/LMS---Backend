<?php
/**
 * @var string $code
 * @var \App\Models\DeepLink|null $deepLink
 */
$name = $deepLink['payload']['name'] ?? null;
$handle = $deepLink['payload']['handle'] ?? null;
$avatarUrl = $deepLink['payload']['avatarUrl'] ?? null;
$isValid = $deepLink !== null;

// TODO: replace once the app is published — same placeholder pattern as
// FriendLinkService's old client-side link (facetalk.app) before this
// backend-backed version existed.
$androidPackage = 'com.jaan.FaceTalk_clone';
$playStoreUrl = "https://play.google.com/store/apps/details?id={$androidPackage}"
    . '&referrer=' . rawurlencode('code=' . $code); // read by android_play_install_referrer on first launch
$appStoreUrl = '#'; // TODO: https://apps.apple.com/app/idXXXXXXXXXX once submitted

$openUrl = url()->current(); // re-tapping this same URL is what lets a verified App/Universal Link hand off to the app post-install
$title = $isValid && $name ? "{$name} invited you to FaceTalk" : 'You were invited to FaceTalk';
$description = $isValid && $handle
    ? "Scan or tap to connect with @{$handle} and start practicing languages together."
    : 'Talk, learn and grow together — a space for students and teachers to connect and practice conversations.';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<?php if ($avatarUrl): ?><meta property="og:image" content="<?= e($avatarUrl) ?>"><?php endif; ?>
<style>
  :root { color-scheme: light; }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    background: #F7F7FA;
    color: #17171C;
  }
  .card {
    width: 100%;
    max-width: 380px;
    background: #fff;
    border-radius: 24px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 6px 30px rgba(20, 20, 40, 0.08);
  }
  .logo {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.3px;
    margin-bottom: 24px;
  }
  .logo span:first-child { color: #3B7AC5; }
  .logo span:last-child { color: #98489A; }
  .avatar {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 16px;
    display: block;
    background: #ECECEF;
  }
  .avatar-fallback {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    margin: 0 auto 16px;
    background: #7B68F4;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
  }
  h1 { font-size: 19px; font-weight: 800; margin: 0 0 6px; }
  p.sub { font-size: 14px; color: #6E6E78; margin: 0 0 28px; line-height: 1.5; }
  .btn {
    display: block;
    width: 100%;
    padding: 14px 16px;
    border-radius: 26px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    margin-bottom: 12px;
    border: none;
    cursor: pointer;
  }
  .btn-primary { background: #7B68F4; color: #fff; }
  .btn-secondary { background: #ECECEF; color: #17171C; }
  .hint { font-size: 12.5px; color: #A3A3AD; margin-top: 18px; line-height: 1.5; }
</style>
</head>
<body>
  <div class="card">
    <div class="logo"><span>Face</span><span>Talk</span></div>

    <?php if ($isValid && $avatarUrl): ?>
      <img class="avatar" src="<?= e($avatarUrl) ?>" alt="">
    <?php elseif ($isValid && $name): ?>
      <div class="avatar-fallback"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></div>
    <?php endif; ?>

    <h1><?= e($title) ?></h1>
    <p class="sub"><?= e($description) ?></p>

    <!-- Already installed: this is the exact link that was tapped/scanned.
         Once the app is verified for App/Universal Links, tapping it again
         hands off to the app directly instead of reopening this page. -->
    <a class="btn btn-primary" href="<?= e($openUrl) ?>">Open in FaceTalk</a>
    <a class="btn btn-secondary" href="<?= e($playStoreUrl) ?>">Get it on Google Play</a>
    <a class="btn btn-secondary" id="app-store-btn" href="<?= e($appStoreUrl) ?>">Download on the App Store</a>

    <p class="hint">
      Already installed FaceTalk? Just tap "Open in FaceTalk" above.<br>
      New here? Install the app, then open this same link again to continue.
    </p>
  </div>
  <script>
    // Android recovers this invite automatically after install via the
    // Play Store URL's referrer param (see the href above). iOS has no
    // equivalent, so this is what deep_link_service.dart's
    // checkClipboardFallback() looks for right after a brand-new signup —
    // best-effort only, never required for the app to work.
    document.getElementById('app-store-btn').addEventListener('click', function () {
      try {
        navigator.clipboard.writeText(<?= json_encode($openUrl) ?>);
      } catch (e) {
        // Clipboard API unavailable (old WebKit, non-HTTPS, ...) — the
        // store link itself still navigates normally either way.
      }
    });
  </script>
</body>
</html>
