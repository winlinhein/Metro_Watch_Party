import express from "express";
import fs from "fs";
import path from "path";
import { createServer } from "http";
import { Server } from "socket.io";

const app = express();
const PORT = 3000;
const httpServer = createServer(app);
const io = new Server(httpServer, {
  cors: { origin: "*" }
});

io.on("connection", (socket) => {
  socket.on("join_chat", (room) => {
    socket.join(room);
  });
  
  socket.on("leave_chat", (room) => {
    socket.leave(room);
  });
  
  socket.on("send_message", (data) => {
    // broadcast to everyone in the room
    io.to(data.room).emit("new_message", data);
  });
});

// Parse POST requests
app.use(express.urlencoded({ extended: true }));
app.use(express.json());

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

app.get("/user_backend/get_reasons.php", (req, res) => {
  res.json({
    success: true,
    reasons: [
      { reason_id: 1, reason_title: "Spam", reason_description: "Unwanted or repetitive content." },
      { reason_id: 2, reason_title: "Harassment", reason_description: "Abusive or threatening behavior." },
      { reason_id: 3, reason_title: "Inappropriate Content", reason_description: "Contains offensive material." }
    ]
  });
});

app.post("/user_backend/submit_report.php", (req, res) => {
  res.json({ success: true, message: "Report submitted successfully." });
});

app.post("/user_backend/unfriend.php", (req, res) => {
  res.json({ success: true, message: "Friend removed." });
});

app.post("/user_backend/clear_notifications.php", (req, res) => {
  res.json({ success: true });
});

app.get("/user_backend/get_chat_history.php", (req, res) => {
  const friendId = req.query.friend_id;
  res.json({
    success: true,
    messages: [
      { message_id: 1, sender_id: friendId, receiver_id: 1, message_text: "Hello from mock!", time: "10:00 AM", is_read: 1 }
    ]
  });
});

app.post("/user_backend/send_chat.php", (req, res) => {
  res.json({ success: true, message_id: Date.now() });
});

app.post("/user_backend/mark_as_read.php", (req, res) => {
  res.json({ success: true });
});


app.get("/backend/dashboard_stats_api.php", (req, res) => {
  res.json([
      { label: 'Total Users', value: '150', change: '+12%', icon: 'group' },
      { label: 'Active Sessions', value: '10', change: '+5%', icon: 'live_tv' },
      { label: 'Revenue', value: '$2,500', change: '+15%', icon: 'payments' },
      { label: 'Server Load', value: '35%', change: '-2%', icon: 'memory' }
  ]);
});
app.post("/user_backend/mark_notifications_read.php", (req, res) => {
  res.json({ success: true });
});

app.get("/user_backend/get_notifications.php", (req, res) => {
  res.json({
    success: true,
    notifications: []
  });
});

app.get("/user_backend/movies_api.php", (req, res) => {
  res.json([
    { 
      id: 1, 
      title: "Inception", 
      genres: ["Sci-Fi", "Action"], 
      year: 2010,
      description: "A thief who steals corporate secrets through the use of dream-sharing technology...",
      img: "https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg",
      cover_image: "https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg",
      trailer: "https://www.youtube.com/watch?v=YoHD9XEInc0",
      actual_video_url: "https://www.w3schools.com/html/mov_bbb.mp4",
      duration: 148,
      view_count: 5020,
      rating: 4.8,
      user_rating: 0
    }
  ]);
});

app.get("/user_backend/get_watchlist.php", (req, res) => {
  res.json({
    success: true,
    watchlist: [
      {
        id: 1,
        title: "Inception",
        year: "2010",
        genre: "Sci-Fi, Action",
        img: "https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster",
        status: "Saved",
        rating: "N/A"
      }
    ]
  });
});

app.post("/user_backend/toggle_watchlist.php", (req, res) => {
  res.json({ success: true, action: "added" });
});

app.get("/user_backend/get_comments.php", (req, res) => {
  res.json({ 
    success: true, 
    comments: [
      {
        id: 101,
        user_name: "MovieCritic99",
        created_at: "2 hours ago",
        content: "This movie was absolutely mind-blowing! The visual effects were insane.",
        likes_count: 42,
        replies: [
          {
            id: 201,
            user_name: "SciFiFanatic",
            created_at: "1 hour ago",
            content: "I completely agree! The ending left me speechless."
          },
          {
            id: 202,
            user_name: "TrollMaster",
            created_at: "45 minutes ago",
            content: "Honestly, it was trash. Overhyped garbage."
          }
        ]
      },
      {
        id: 102,
        user_name: "CasualViewer",
        created_at: "5 hours ago",
        content: "A bit slow in the middle, but overall a solid watch.",
        likes_count: 15,
        replies: [
          {
            id: 203,
            user_name: "ActionJunkie",
            created_at: "3 hours ago",
            content: "Agreed, pacing was a bit off during the second act."
          }
        ]
      }
    ] 
  });
});

app.post("/user_backend/like_comment.php", (req, res) => {
  res.json({ success: true, likes: 1 });
});

app.post("/user_backend/rate_movie.php", (req, res) => {
  res.json({ success: true, rating: 5 });
});

app.post("/user_backend/post_comment.php", (req, res) => {
  res.json({ success: true, comment: { comment_id: Date.now(), user_name: "MockUser", content: req.body?.content || "Mock comment", created_at: new Date().toISOString() } });
});

app.post("/user_backend/post_reply.php", (req, res) => {
  res.json({ success: true, reply: { reply_id: Date.now(), user_name: "MockUser", content: req.body?.content || "Mock reply", created_at: new Date().toISOString() } });
});

app.post("/user_backend/respond_friend.php", (req, res) => {
  res.json({ success: true });
});

app.post("/user_backend/add_friend.php", (req, res) => {
  res.json({ success: true, message: "Friend added successfully." });
});

app.get("/backend/users_api.php", (req, res) => {
  res.json([
    { id: 1, name: "Alice", email: "alice@example.com", status: "Active", role: "Admin", points: 1250 },
    { id: 2, name: "Bob", email: "bob@example.com", status: "Active", role: "Premium", points: 840 },
    { id: 3, name: "Charlie", email: "charlie@example.com", status: "Banned", role: "Standard", points: 15 },
    { id: 4, name: "Diana", email: "diana@example.com", status: "Pending", role: "Standard", points: 0 },
    { id: 5, name: "Eve", email: "eve@example.com", status: "Active", role: "Standard", points: 300 },
    { id: 6, name: "Frank", email: "frank@example.com", status: "Active", role: "Moderator", points: 400 }
  ]);
});

app.post("/backend/users_api.php", (req, res) => {
  res.json({ success: true, message: "Action executed (Mock)." });
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

app.get("/backend/get_reports.php", (req, res) => {
  res.json({ success: true, reports: [] });
});

app.post("/backend/update_report_status.php", (req, res) => {
  res.json({ success: true, message: "Report status updated." });
});

app.post("/backend/resend_otp.php", (req, res) => {
  res.json({ success: true, message: "OTP resent successfully (Mock)." });
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

httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`Server running on port ${PORT}`);
});
