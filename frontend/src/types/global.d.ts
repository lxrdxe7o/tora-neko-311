/**
 * Global TypeScript Definitions
 */

declare module '*.svg' {
  const content: string;
  export default content;
}

declare module '*.svg?raw' {
  const content: string;
  export default content;
}

declare module '*.css' {
  const content: Record<string, string>;
  export default content;
}

// Extend Window interface if needed
interface Window {
  // Add any global window properties here
}
