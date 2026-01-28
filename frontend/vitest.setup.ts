import "@testing-library/jest-dom";
import { vi } from "vitest";

// Polyfill for PointerEvent methods not implemented in JSDOM
// Needed for testing components based on Radix UI (like shadcn/ui)
if (typeof window !== "undefined") {
  const pointerCaptureMap = new WeakMap<Element, Set<number>>();

  if (!window.Element.prototype.setPointerCapture) {
    window.Element.prototype.setPointerCapture = function setPointerCapture(pointerId: number) {
      const existing = pointerCaptureMap.get(this) ?? new Set<number>();
      existing.add(pointerId);
      pointerCaptureMap.set(this, existing);
    };
    window.Element.prototype.releasePointerCapture = function releasePointerCapture(pointerId: number) {
      const existing = pointerCaptureMap.get(this);
      if (existing) {
        existing.delete(pointerId);
      }
    };
    window.Element.prototype.hasPointerCapture = function hasPointerCapture(pointerId: number) {
      const existing = pointerCaptureMap.get(this);
      return existing?.has(pointerId) ?? false;
    };
  }
  if (!window.Element.prototype.scrollIntoView) {
    window.Element.prototype.scrollIntoView = function scrollIntoView() {
      return undefined;
    };
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
