package by.donmap.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import by.donmap.app.ui.home.HomeScreen
import by.donmap.app.ui.theme.DonMapTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        val app = application as DonMapApp
        setContent {
            DonMapTheme {
                HomeScreen(
                    apiClient = app.apiClient,
                    localeManager = app.localeManager,
                )
            }
        }
    }
}
