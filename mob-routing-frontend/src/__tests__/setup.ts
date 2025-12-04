// Mock des imports CSS
import { vi } from 'vitest'

// Mock tous les imports .css
vi.mock('*.css', () => ({}))
vi.mock('*.scss', () => ({}))
