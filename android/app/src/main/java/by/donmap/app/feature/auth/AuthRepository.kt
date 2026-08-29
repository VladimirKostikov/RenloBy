package by.donmap.app.feature.auth

import by.donmap.app.api.ApiClient
import org.json.JSONObject

class AuthRepository(private val apiClient: ApiClient) {
    fun login(email: String, password: String): Result<Unit> = runCatching {
        apiClient.post(
            "/api/auth/login",
            JSONObject().put("email", email).put("password", password),
        )
    }

    fun logout(): Result<Unit> = runCatching {
        apiClient.post("/api/auth/logout", JSONObject())
    }

    fun me(): Result<JSONObject> = runCatching {
        apiClient.get("/api/auth/me")
    }
}
