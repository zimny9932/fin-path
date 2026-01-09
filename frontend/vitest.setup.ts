import "@testing-library/jest-dom";

// Polyfill for PointerEvent methods not implemented in JSDOM
// Needed for testing components based on Radix UI (like shadcn/ui)
if (typeof window !== "undefined") {
  if (!window.Element.prototype.setPointerCapture) {
    window.Element.prototype.setPointerCapture = function (_pointerId) {};
    window.Element.prototype.releasePointerCapture = function (_pointerId) {};
    window.Element.prototype.hasPointerCapture = function (_pointerId) {
      return false;
    };
  }
  if (!window.Element.prototype.scrollIntoView) {
    window.Element.prototype.scrollIntoView = function () {};
  }
}

// Mock window.matchMedia for Sonner library
Object.defineProperty(window, "matchMedia", {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // deprecated
    removeListener: vi.fn(), // deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});
