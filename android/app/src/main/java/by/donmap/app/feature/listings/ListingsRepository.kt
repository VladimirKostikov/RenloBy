package by.donmap.app.feature.listings

import by.donmap.app.api.ApiClient
import org.json.JSONArray
import org.json.JSONObject

data class ListingItem(
    val id: String,
    val title: String,
    val price: String,
    val rooms: Int,
    val area: Double,
)

class ListingsRepository(private val apiClient: ApiClient) {
    fun search(query: String = ""): Result<List<ListingItem>> = runCatching {
        val path = if (query.isBlank()) "/api/listings" else "/api/listings?q=${query.trim()}"
        val response = apiClient.get(path)
        val items = response.optJSONArray("items") ?: response.optJSONArray("member") ?: JSONArray()
        buildList {
            for (i in 0 until items.length()) {
                add(parseItem(items.getJSONObject(i)))
            }
        }
    }

    private fun parseItem(json: JSONObject): ListingItem = ListingItem(
        id = json.optString("id", json.optString("@id")),
        title = json.optString("title", json.optString("address", "—")),
        price = json.optString("priceFormatted", json.opt("price")?.toString() ?: "—"),
        rooms = json.optInt("rooms", 0),
        area = json.optDouble("area", 0.0),
    )
}
