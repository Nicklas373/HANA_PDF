import { execSync } from 'child_process';

function getGitCommitHash() {
  try {
    const hash = execSync('git rev-parse --short HEAD').toString().trim();
    return hash;
  } catch (error) {
    console.error('Error getting Git commit hash:', error);
    return null;
  }
}

export default getGitCommitHash;
