import express from "express";
import fs from "fs";
import path from "path";

const app = express();
const PORT = 3000;

// Parse POST requests
app.use(express.urlencoded({ extended: true }));

// Mock backend route for backend.php
app.post("/backend/backend.php", (req, res) => {
  const action = req.query.action;
  
  if (action === "login") {
    const { email, password } = req.body;
    if (!email || !password) {
      res.redirect("/frontend/index.php?error=" + encodeURIComponent("Please fill in all fields."));
    } else {
      res.redirect("/frontend/otp-login.php");
    }
  } else if (action === "register") {
    const { name, email, password, terms } = req.body;
    if (!name || !email || !password || !terms) {
      res.redirect("/frontend/register.php?error=" + encodeURIComponent("Please fill in all required fields."));
    } else {
      res.redirect("/frontend/register.php?error=" + encodeURIComponent("Account creation simulated successfully."));
    }
  } else if (action === "forgot_password") {
    const { email } = req.body;
    if (!email) {
      res.redirect("/frontend/forgot-password.php?error=" + encodeURIComponent("Please enter your email address."));
    } else {
      res.redirect("/frontend/otp-forgot.php");
    }
  } else if (action === "verify_otp_login") {
    const { otp } = req.body;
    if (!otp || otp.length !== 6) {
      res.redirect("/frontend/otp-login.php?error=" + encodeURIComponent("Please enter a valid 6-digit code."));
    } else {
      res.redirect("/frontend/otp-login.php?error=" + encodeURIComponent("2FA successful! (Mock PHP response)"));
    }
  } else if (action === "verify_otp_forgot") {
    const { otp } = req.body;
    if (!otp || otp.length !== 6) {
      res.redirect("/frontend/otp-forgot.php?error=" + encodeURIComponent("Please enter a valid 6-digit code."));
    } else {
      res.redirect("/frontend/otp-forgot.php?error=" + encodeURIComponent("Code verified! (Mock PHP response)"));
    }
  } else {
    res.redirect("/frontend/index.php");
  }
});

app.post("/backend/logout.php", (req, res) => {
  res.json({
    success: true,
    message: 'Signed out successfully.',
    redirect: '/frontend/login.php?success=' + encodeURIComponent("You have been signed out.")
  });
});

app.get("/user_backend/search_users.php", (req, res) => {
  const query = req.query.q as string || "";
  const users = [
    { user_id: 1, user_name: "Alice", email: "alice@example.com", is_premium: 1 },
    { user_id: 2, user_name: "Bob", email: "bob@example.com", is_premium: 0 },
    { user_id: 3, user_name: "Charlie", email: "charlie@example.com", is_premium: 1 }
  ];
  if (!query) return res.json(users);
  res.json(users.filter(u => 
    u.user_name.toLowerCase().includes(query.toLowerCase()) || 
    u.email.toLowerCase().includes(query.toLowerCase())
  ));
});

app.get("/user_backend/get_friends.php", (req, res) => {
  res.json({
    friends: [
      { friend_id: 1, user_name: "Alice", email: "alice@example.com", is_premium: 1 }
    ],
    pending_requests: []
  });
});

app.get("/user_backend/mission.php", (req, res) => {
  res.json({
    success: true,
    totalPoints: 1250,
    quests: {
      daily: [{ mission_id: 1, title: "Watch a movie", points_reward: 50, completed: 0 }],
      weekly: [{ mission_id: 2, title: "Host a watch party", points_reward: 200, completed: 0 }],
      monthly: [{ mission_id: 3, title: "Watch 10 movies", points_reward: 1000, completed: 0 }]
    }
  });
});

app.get("/user_backend/get_notifications.php", (req, res) => {
  res.json({
    success: true,
    notifications: []
  });
});

app.get("/user_backend/movies_api.php", (req, res) => {
  res.json([
    { id: 1, title: "Inception", genres: ["Sci-Fi", "Action"], year: 2010 }
  ]);
});

app.post("/user_backend/add_friend.php", (req, res) => {
  res.json({ success: true, message: "Friend added successfully." });
});

app.get("/backend/users_api.php", (req, res) => {
  res.json([]);
});

app.get("/backend/movies_api.php", (req, res) => {
  res.json([]);
});

app.post("/backend/movies_api.php", (req, res) => {
  res.json({ success: true, message: "Movie updated." });
});

app.get("/backend/genres_api.php", (req, res) => {
  res.json([]);
});

app.post("/backend/update_profile.php", (req, res) => {
  res.json({ success: true });
});

app.post("/backend/delete_account.php", (req, res) => {
  res.json({ success: true });
});

function handlePhpRequest(req: express.Request, res: express.Response) {
  let requestPath = req.path;
  if (requestPath === "/") {
      return res.redirect("/user/dashboard.php");
  }
  if (!requestPath.endsWith(".php")) {
      requestPath = requestPath + ".php"; // very crude fallback
  }

  const filePath = path.join(process.cwd(), requestPath);
  if (!fs.existsSync(filePath)) {
      // Fallback to index.php if not found, just in case
      const indexPath = path.join(process.cwd(), "frontend", "index.php");
      if (fs.existsSync(indexPath)) {
        let content = fs.readFileSync(indexPath, "utf-8");
        content = processPhpMockup(content, req, "frontend");
        res.setHeader("Content-Type", "text/html");
        return res.send(content);
      }
      return res.status(404).send("Not found");
  }
  let content = fs.readFileSync(filePath, "utf-8");
  
  const currentDir = path.dirname(requestPath.replace(/^\//, ""));
  content = processPhpMockup(content, req, currentDir);
  res.setHeader("Content-Type", "text/html");
  res.send(content);
}

// Serve the PHP file as HTML for preview purposes
app.get("*.php", handlePhpRequest);
app.get("/", handlePhpRequest);

// Serve static files
app.use(express.static(process.cwd(), { index: false }));

app.get("*", handlePhpRequest);

function processPhpMockup(content: string, req: express.Request, currentDir: string = "frontend") {
  // Basic mockup of PHP execution for the preview environment
  const errorMsg = req.query.error as string;
  if (errorMsg) {
    content = content.replace("<?php if (isset($_GET['error'])): ?>", "");
    content = content.replace("<?php endif; ?>", "");
    content = content.replace("<?= htmlspecialchars(urldecode($_GET['error'])) ?>", errorMsg);
  } else {
    // Hide error block if no error
    content = content.replace(/<\?php if \(isset\(\$_GET\['error'\]\)\): \?>[\s\S]*?<\?php endif; \?>/g, "");
  }

  const messageMsg = req.query.message as string;
  if (messageMsg) {
    content = content.replace("<?php if (isset($_GET['message'])): ?>", "");
    content = content.replace("<?php endif; ?>", "");
    content = content.replace("<?= htmlspecialchars(urldecode($_GET['message'])) ?>", messageMsg);
  } else {
    // Hide message block if no message
    content = content.replace(/<\?php if \(isset\(\$_GET\['message'\]\)\): \?>[\s\S]*?<\?php endif; \?>/g, "");
  }


  // Process includes
  
  let hasIncludes = true;
  let depth = 0;
  while (hasIncludes && depth < 5) {
    hasIncludes = false;
    const includeRegex1 = /<\?php\s+include\s+__DIR__\s*\.\s*['"]([^'"]+)['"]\s*;?\s*\?>/g;
    content = content.replace(includeRegex1, (match, p1) => {
      hasIncludes = true;
      try {
        let includePath = path.join(process.cwd(), currentDir, p1.replace(/^\//, ''));
        if (!fs.existsSync(includePath)) {
            includePath = path.join(process.cwd(), currentDir, 'views', p1.replace(/^\//, ''));
        }
        return fs.existsSync(includePath) ? fs.readFileSync(includePath, 'utf8') : '';
      } catch(e) { return ''; }
    });

    const includeRegex2 = /<\?php\s+include\s+['"]([^'"]+)['"]\s*;?\s*\?>/g;
    content = content.replace(includeRegex2, (match, p1) => {
      hasIncludes = true;
      try {
        const includePath = path.join(process.cwd(), currentDir, p1.replace(/^\//, ''));
        return fs.existsSync(includePath) ? fs.readFileSync(includePath, 'utf8') : '';
      } catch(e) { return ''; }
    });
    depth++;
  }


  

  // Process isBarba
  const isBarba = req.headers["x-barba"] === "yes";
  content = content.replace(/<\?php echo \$isBarba \? "x-ignore" : ""; \?>/g, isBarba ? "x-ignore" : "");

  // Remove other PHP tags to prevent displaying them in the browser
  content = content.replace(/<\?php[\s\S]*?\?>/g, "");

  return content;
}

app.listen(PORT, "0.0.0.0", () => {
  console.log(`Server running on port ${PORT}`);
});
