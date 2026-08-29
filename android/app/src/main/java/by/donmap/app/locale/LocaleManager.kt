package by.donmap.app.locale

import android.content.Context
import org.json.JSONObject
import java.io.BufferedReader

class LocaleManager(context: Context) {
    private val strings: JSONObject

    init {
        context.assets.open("locales/ru.json").use { stream ->
            val text = stream.bufferedReader().use(BufferedReader::readText)
            strings = JSONObject(text)
        }
    }

    fun t(key: String): String {
        val parts = key.split('.')
        var node: Any = strings
        for (part in parts) {
            node = (node as JSONObject).get(part)
        }
        return node as String
    }
}
