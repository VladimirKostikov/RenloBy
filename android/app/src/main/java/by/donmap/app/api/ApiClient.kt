package by.donmap.app.api

import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.io.IOException
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.TimeUnit

class ApiClient(baseUrl: String) {
    private val jsonMediaType = "application/json".toMediaType()
    private val cookieStore = ConcurrentHashMap<String, MutableList<Cookie>>()

    private val client = OkHttpClient.Builder()
        .cookieJar(object : CookieJar {
            override fun loadForRequest(url: HttpUrl): List<Cookie> =
                cookieStore[url.host]?.filter { it.expiresAt > System.currentTimeMillis() } ?: emptyList()

            override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
                cookieStore.getOrPut(url.host) { mutableListOf() }.apply {
                    cookies.forEach { cookie ->
                        removeAll { it.name == cookie.name && it.path == cookie.path }
                        add(cookie)
                    }
                }
            }
        })
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build()

    private val normalizedBase = baseUrl.trimEnd('/')

    @Throws(IOException::class)
    fun get(path: String): JSONObject {
        val request = Request.Builder()
            .url("$normalizedBase$path")
            .header("Accept", "application/json")
            .get()
            .build()
        client.newCall(request).execute().use { response ->
            val body = response.body?.string() ?: "{}"
            if (!response.isSuccessful) throw IOException("HTTP ${response.code}")
            return JSONObject(body)
        }
    }

    @Throws(IOException::class)
    fun post(path: String, payload: JSONObject): JSONObject {
        val request = Request.Builder()
            .url("$normalizedBase$path")
            .header("Accept", "application/json")
            .header("Content-Type", "application/json")
            .post(payload.toString().toRequestBody(jsonMediaType))
            .build()
        client.newCall(request).execute().use { response ->
            val body = response.body?.string() ?: "{}"
            if (!response.isSuccessful) throw IOException("HTTP ${response.code}")
            return JSONObject(body)
        }
    }
}
