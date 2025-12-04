// src/__tests__/services/api.test.ts
import { describe, it, expect, beforeEach, vi } from 'vitest'
import axios from 'axios'

// Mock localStorage
const localStorageMock = {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn(),
    clear: vi.fn(),
}
global.localStorage = localStorageMock as any

// Mock axios AVANT l'import du service
vi.mock('axios', () => {
    return {
        default: {
            create: vi.fn(() => ({
                post: vi.fn(),
                get: vi.fn(),
                interceptors: {
                    request: { use: vi.fn(), eject: vi.fn() },
                    response: { use: vi.fn(), eject: vi.fn() },
                },
            })),
            post: vi.fn(),
            get: vi.fn(),
        },
    }
})

import { calculateRoute, getDistanceStats, loginApi, getStoredToken, isAuthenticated } from '../../services/api'
import type { RouteRequest, AnalyticDistanceList } from '../../types'

describe('ApiService', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    describe('calculateRoute', () => {
        it('should be defined', () => {
            expect(calculateRoute).toBeDefined()
            expect(typeof calculateRoute).toBe('function')
        })
    })

    describe('getDistanceStats', () => {
        it('should be defined', () => {
            expect(getDistanceStats).toBeDefined()
            expect(typeof getDistanceStats).toBe('function')
        })
    })

    describe('loginApi', () => {
        it('should be defined', () => {
            expect(loginApi).toBeDefined()
            expect(typeof loginApi).toBe('function')
        })
    })

    describe('getStoredToken', () => {
        it('should be defined', () => {
            expect(getStoredToken).toBeDefined()
            expect(typeof getStoredToken).toBe('function')
        })

        it('should return token from localStorage', () => {
            const mockToken = 'test-token-123'
            localStorageMock.getItem.mockReturnValue(mockToken)

            const token = getStoredToken()

            expect(token).toBe(mockToken)
            expect(localStorageMock.getItem).toHaveBeenCalledWith('jwt_token')
        })
    })

    describe('isAuthenticated', () => {
        it('should be defined', () => {
            expect(isAuthenticated).toBeDefined()
            expect(typeof isAuthenticated).toBe('function')
        })

        it('should return true when token exists', () => {
            localStorageMock.getItem.mockReturnValue('test-token')

            const result = isAuthenticated()

            expect(result).toBe(true)
        })

        it('should return false when token does not exist', () => {
            localStorageMock.getItem.mockReturnValue(null)

            const result = isAuthenticated()

            expect(result).toBe(false)
        })
    })
})
