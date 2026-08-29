package by.donmap.app.feature.favorites

import by.donmap.app.api.ApiClient
import org.json.JSONArray
import org.json.JSONObject

class FavoritesRepository(private val apiClient: ApiClient) {
    fun list(): Result<List<String>> = runCatching {
        val response = apiClient.get("/api/favorites")
        val items = response.optJSONArray("items") ?: JSONArray()
        buildList {
            for (i in 0 until items.length()) {
                add(items.getJSONObject(i).optString("listingId"))
            }
        }
    }

    fun toggle(listingId: String): Result<Unit> = runCatching {
        apiClient.post(
            "/api/favorites/toggle",
            JSONObject().put("listingId", listingId),
        )
    }
}
