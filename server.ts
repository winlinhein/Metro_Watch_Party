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

function handlePhpRequest(req: express.Request, res: express.Response) {
  let requestPath = req.path;
  if (requestPath === "/") {
      return res.redirect("/frontend/index.php");
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
        content = processPhpMockup(content, req);
        res.setHeader("Content-Type", "text/html");
        return res.send(content);
      }
      return res.status(404).send("Not found");
  }
  let content = fs.readFileSync(filePath, "utf-8");
  
  content = processPhpMockup(content, req);
  res.setHeader("Content-Type", "text/html");
  res.send(content);
}

// Serve the PHP file as HTML for preview purposes
app.get("*.php", handlePhpRequest);
app.get("/", handlePhpRequest);

// Serve static files
app.use(express.static(process.cwd(), { index: false }));

app.get("*", handlePhpRequest);

function processPhpMockup(content: string, req: express.Request) {
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
        const includePath = path.join(process.cwd(), 'frontend', p1.replace(/^\//, ''));
        return fs.existsSync(includePath) ? fs.readFileSync(includePath, 'utf8') : '';
      } catch(e) { return ''; }
    });

    const includeRegex2 = /<\?php\s+include\s+['"]([^'"]+)['"]\s*;?\s*\?>/g;
    content = content.replace(includeRegex2, (match, p1) => {
      hasIncludes = true;
      try {
        const includePath = path.join(process.cwd(), 'frontend', p1.replace(/^\//, ''));
        return fs.existsSync(includePath) ? fs.readFileSync(includePath, 'utf8') : '';
      } catch(e) { return ''; }
    });
    depth++;
  }


  

  // Remove other PHP tags to prevent displaying them in the browser
  content = content.replace(/<\?php[\s\S]*?\?>/g, "");

  return content;
}

app.listen(PORT, "0.0.0.0", () => {
  console.log(`Server running on port ${PORT}`);
});
