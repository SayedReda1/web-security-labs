const express = require('express');
const { fork } = require('child_process');

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

function processProfile(profile) {
  // Normal application code: an option object is created without execArgv.
  const options = {};

  // Prototype pollution makes options.execArgv resolve through Object.prototype.
  // This is the vulnerable application-side gadget that feeds fork().
  const forkOptions = {
    execArgv: options.execArgv,
    stdio: ['ignore', 'pipe', 'pipe', 'ipc']
  };

  fork(require.resolve('./worker.js'), [], forkOptions);
}

// Single realistic endpoint: update a user's profile.
app.post('/profile/update', async (req, res) => {
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

  await processProfile(user);

  res.json({
    message: 'profile updated',
    user
  });
});

app.listen(3000, () => {
  console.log(
    'server listening on http://127.0.0.1:3000'
  );
});