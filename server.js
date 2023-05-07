const express = require('express');
const mongoose = require('mongoose');
const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const morgan = require('morgan');
const { check, validationResult } = require('express-validator');

const { createServer } = require('http');
const { Server } = require('socket.io');

const helmet = require('helmet');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const mongoSanitize = require('express-mongo-sanitize');
const xss = require('xss-clean');
const hpp = require('hpp');

require('dotenv').config();

const app = express();
const port = process.env.PORT || 3000;

// Connect to MongoDB database
mongoose.connect(process.env.MONGODB_URI, { useNewUrlParser: true, useUnifiedTopology: true })
  .then(() => {
    console.log('Connected to MongoDB');
  })
  .catch((error) => {
    console.error(error);
  });

// Create user schema
const userSchema = new mongoose.Schema({
    username: { 
        type: String,
        required: true,
        minlength: 5,
        maxlength: 20, 
        unique: true 
    },
    email: { 
        type: String,
        required: true, 
        unique: true,
        match: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    },
    password: {
        type: String,
        required: true,
    },
    posts: [{ type: mongoose.Types.ObjectId, ref: 'Post' }],
});  

// Create user model
const User = mongoose.model('User', userSchema);


// Define the "post" schema
const postSchema = new mongoose.Schema({
    title: {
      type: String,
      required: true
    },
    content: {
      type: String,
      required: true
    },
    user: { 
        type: mongoose.Types.ObjectId,
        required: true, 
        ref: 'User' 
    } 
});

// Create the "Post" model
const Post = mongoose.model('Post', postSchema);

// Middleware to parse incoming requests with JSON payloads
app.use(express.json());


// Set up middleware
app.use(helmet()); // Set various HTTP headers to improve security
app.use(cors()); // Enable CORS for cross-origin requests
app.use(express.json()); // Parse JSON request bodies
app.use(express.urlencoded({ extended: true })); // Parse URL-encoded request bodies
app.use(mongoSanitize()); // Remove unsafe characters from MongoDB queries
app.use(xss()); // Prevent cross-site scripting attacks
app.use(hpp()); // Prevent HTTP parameter pollution


// Use a custom log format
app.use(morgan(':method :url :status :response-time ms'));


// Set up rate limiting to prevent abuse
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
  });
app.use(limiter);

// Route to create a new user
app.post('/signup', [
    check('username').isLength({ min: 5 }).withMessage('Username must be at least 5 characters long'),
    check('email').isEmail().normalizeEmail(),
    check('password').isLength({ min: 8 }).withMessage('Password must be at least 8 characters long'),
  ], async (req, res) => {
    try {

      // Validate request body
      const errors = validationResult(req);
        if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }    

      const { username, email, password } = req.body;
  
      // Hash the password using bcrypt
      const hashedPassword = await bcrypt.hash(password, 10);
  
      // Create new user
      const user = new User({ username, email, password: hashedPassword });
  
      // Save user to database
      await user.save();
  
      res.json({ message: 'User created successfully' });
    } catch (error) {
      console.error(error);
      res.status(500).json({ error: 'Internal server error' });
    }
});

// Route to login with an existing user
app.post('/login', 
    [
    check('username').isLength({ min: 5 }).withMessage('Username must be at least 5 characters long'),
    check('password').isLength({ min: 8 }).withMessage('Password must be at least 8 characters long'),
    ], async (req, res) => {
    try {

      // Validate request body
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }  

      const { username, password } = req.body;
  
      // Find user by username
      const user = await User.findOne({ username });
  
      // Check if user exists
      if (!user) {
        return res.status(401).json({ error: 'Invalid username or password' });
      }
  
      // Check if password matches
      const isPasswordValid = await bcrypt.compare(password, user.password);
      if (!isPasswordValid) {
        return res.status(401).json({ error: 'Invalid username or password' });
      }
  
      // Generate JWT token
    const token = jwt.sign({username, password}, process.env.JWT_SECRET);

    res.json({
        token,
        message: 'Login successful'
    });
    } catch (error) {
      console.error(error);
      res.status(500).json({ error: 'Internal server error' });
    }
});

// Route to access protected resource
app.get('/protected', (req, res) => {
  // Check for valid token
  const authHeader = req.headers.authorization;
  if (!authHeader) {
    return res.status(401).json({
      error: 'Authorization header missing',
    });
  }

  const token = authHeader.split(' ')[1];
  try {
    const user = jwt.verify(token, process.env.JWT_SECRET);
    res.json({
      message: 'Protected resource accessed successfully',
      user,
    });
  } catch (err) {
    res.status(401).json({
      error: 'Invalid token',
    });
  }
});

app.post('/createPost', async(req, res) => {

    // Check for valid token
    const authHeader = req.headers.authorization;
    if (!authHeader) {
        return res.status(401).json({
        error: 'Authorization header missing',
        });
    }

    const token = authHeader.split(' ')[1];
    let user = {};
    try {
        user = jwt.verify(token, process.env.JWT_SECRET);
    } catch (err) {
        res.status(401).json({
        error: 'Invalid token',
        });
    }

  const author = await User.findOne({username: user.username})

  console.log("author", author.posts);

    const post = new Post({
        title: req.body.title,
        content: req.body.content,
        user: author._id,
    })

    try {
        await post.save();
        author.posts.push(post)
        await author.save();
        res.json({
            message: "Post created successfully!"
        })
    } catch (error) {
        res.json({
            error
        })
    }
})


app.get("/allPosts", async(req, res) => {
    try {
        const posts = await Post.find().populate('user');
        res.json({posts})
    } catch (error) {
        res.json({
            error
        })
    }
})


// Set up Socket.IO server
const httpServer = createServer(app);
const io = new Server(httpServer);
io.on('connection', (socket) => {
  console.log('User connected:', socket.id);
  socket.on('disconnect', () => {
    console.log('User disconnected:', socket.id);
  });
});

// Start server
app.listen(port, () => {
  console.log(`Server listening on port ${port}`);
});
