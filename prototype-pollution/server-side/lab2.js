const express = require('express');
const { execSync } = require('child_process');

const app = express();
app.use(express.json());

const users = {
  alice: {
    name: 'Alice',
    email: 'alice@example.test',
    bio: 'Security student'
  }
};

// Intentionally vulnerable deep merge used to update a user's profile.
// The mistake is assuming every nested key is safe to merge into the target.
function merge(target, source) {
  for (const [key, value] of Object.entries(source)) {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      if (!target[key] || typeof target[key] !== 'object') {
        target[key] = {};
      }
      merge(target[key], value);
    } else {
      target[key] = value;
    }
  }

  return target;
}

function saveProfile(profile) {
  // Imagine this being a legitimate maintenance command used after updates.
  // The application never explicitly sets shell/input.
  const options = {};

  // Prototype-polluted properties are inherited by `options`, then copied
  // into the actual execSync options object used by the application.
  const execOptions = {
    shell: options.shell,
    input: options.input
  };

  return execSync('whoami', execOptions).toString();
}

// Single realistic endpoint: update a user's profile.
app.post('/profile/update', (req, res) => {
  const { userId, profile } = req.body;

  if (!userId || !profile || typeof profile !== 'object') {
    return res.status(400).json({
      error: 'userId and profile are required'
    });
  }

  const user = users[userId];

  if (!user) {
    return res.status(404).json({
      error: 'user not found'
    });
  }

  merge(user, profile);

  try {
    const output = saveProfile(user);

    res.json({
      message: 'profile updated',
      user,
      output
    });
  } catch (err) {
    res.status(500).json({
      message: 'profile update triggered an application error',
      error: String(err.message),
      user
    });
  }
});

app.listen(3001, () => {
  console.log(
    'server listening on http://127.0.0.1:3001'
  );
});