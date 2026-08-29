import Foundation

public struct ListingItem: Identifiable, Sendable {
    public let id: String
    public let title: String
    public let price: String
    public let rooms: Int
    public let area: Double

    public init(id: String, title: String, price: String, rooms: Int, area: Double) {
        self.id = id
        self.title = title
        self.price = price
        self.rooms = rooms
        self.area = area
    }
}

public enum ApiError: Error {
    case invalidResponse
    case httpStatus(Int)
}

public final class ApiClient: @unchecked Sendable {
    private let baseURL: URL
    private let session: URLSession

    public init(baseURL: URL, session: URLSession = .shared) {
        self.baseURL = baseURL
        self.session = session
        session.configuration.httpCookieStorage = HTTPCookieStorage.shared
        session.configuration.httpShouldSetCookies = true
    }

    public func get(path: String) async throws -> [String: Any] {
        let url = baseURL.appendingPathComponent(path.trimmingCharacters(in: CharacterSet(charactersIn: "/")))
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw ApiError.invalidResponse }
        guard (200..<300).contains(http.statusCode) else { throw ApiError.httpStatus(http.statusCode) }
        let json = try JSONSerialization.jsonObject(with: data) as? [String: Any]
        return json ?? [:]
    }

    public func post(path: String, body: [String: Any]) async throws -> [String: Any] {
        let url = baseURL.appendingPathComponent(path.trimmingCharacters(in: CharacterSet(charactersIn: "/")))
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: body)
        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw ApiError.invalidResponse }
        guard (200..<300).contains(http.statusCode) else { throw ApiError.httpStatus(http.statusCode) }
        let json = try JSONSerialization.jsonObject(with: data) as? [String: Any]
        return json ?? [:]
    }
}

public final class ListingsRepository: @unchecked Sendable {
    private let apiClient: ApiClient

    public init(apiClient: ApiClient) {
        self.apiClient = apiClient
    }

    public func search(query: String = "") async throws -> [ListingItem] {
        let path = query.isEmpty ? "/api/listings" : "/api/listings?q=\(query.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? query)"
        let response = try await apiClient.get(path: path)
        let rawItems = (response["items"] as? [[String: Any]]) ?? (response["member"] as? [[String: Any]]) ?? []
        return rawItems.map(parseItem)
    }

    private func parseItem(_ json: [String: Any]) -> ListingItem {
        ListingItem(
            id: (json["id"] as? String) ?? (json["@id"] as? String) ?? UUID().uuidString,
            title: (json["title"] as? String) ?? (json["address"] as? String) ?? "—",
            price: (json["priceFormatted"] as? String) ?? String(describing: json["price"] ?? "—"),
            rooms: json["rooms"] as? Int ?? 0,
            area: json["area"] as? Double ?? 0
        )
    }
}

public final class AuthRepository: @unchecked Sendable {
    private let apiClient: ApiClient

    public init(apiClient: ApiClient) {
        self.apiClient = apiClient
    }

    public func login(email: String, password: String) async throws {
        _ = try await apiClient.post(path: "/api/auth/login", body: ["email": email, "password": password])
    }

    public func logout() async throws {
        _ = try await apiClient.post(path: "/api/auth/logout", body: [:])
    }
}

public final class FavoritesRepository: @unchecked Sendable {
    private let apiClient: ApiClient

    public init(apiClient: ApiClient) {
        self.apiClient = apiClient
    }

    public func toggle(listingId: String) async throws {
        _ = try await apiClient.post(path: "/api/favorites/toggle", body: ["listingId": listingId])
    }
}
