package hu.paksiinformatika.mobilblokkolo

import android.content.Intent
import android.graphics.BitmapFactory
import android.net.Uri
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.RecyclerView
import com.google.android.material.card.MaterialCardView
import hu.paksiinformatika.mobilblokkolo.data.local.EventEntity
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

data class EventListItem(
    val event: EventEntity,
    val employeeName: String
)

class EventAdapter(
    private val items: List<EventListItem>
) : RecyclerView.Adapter<EventAdapter.EventViewHolder>() {

    class EventViewHolder(
        itemView: View
    ) : RecyclerView.ViewHolder(itemView) {

        val imgPhoto: ImageView =
            itemView.findViewById(R.id.imgPhoto)

        val tvEmployeeName: TextView =
            itemView.findViewById(R.id.tvEmployeeName)

        val tvDate: TextView =
            itemView.findViewById(R.id.tvDate)

        val tvGps: TextView =
            itemView.findViewById(R.id.tvGps)

        val tvSyncStatus: TextView =
            itemView.findViewById(R.id.tvSync)
    }

    override fun onCreateViewHolder(
        parent: ViewGroup,
        viewType: Int
    ): EventViewHolder {

        val view =
            LayoutInflater
                .from(parent.context)
                .inflate(
                    R.layout.item_event,
                    parent,
                    false
                )

        return EventViewHolder(view)
    }

    override fun onBindViewHolder(
        holder: EventViewHolder,
        position: Int
    ) {

        val item =
            items[position]

        val event =
            item.event

        val card =
            holder.itemView as MaterialCardView

        if (event.eventType == "IN") {
            card.setCardBackgroundColor(
                ContextCompat.getColor(
                    holder.itemView.context,
                    R.color.event_in_background
                )
            )
            card.strokeColor =
                ContextCompat.getColor(
                    holder.itemView.context,
                    R.color.event_in_stroke
                )
        } else {
            card.setCardBackgroundColor(
                ContextCompat.getColor(
                    holder.itemView.context,
                    R.color.event_out_background
                )
            )
            card.strokeColor =
                ContextCompat.getColor(
                    holder.itemView.context,
                    R.color.event_out_stroke
                )
        }

        card.strokeWidth =
            holder.itemView.resources.getDimensionPixelSize(
                R.dimen.event_card_stroke_width
            )

        holder.tvEmployeeName.text =
            item.employeeName

        val formatter =
            SimpleDateFormat(
                "yyyy-MM-dd HH:mm:ss",
                Locale.getDefault()
            )

        holder.tvDate.text =
            formatter.format(
                Date(event.timestamp)
            )

        holder.tvGps.text =
            if (
                event.latitude != null &&
                event.longitude != null
            ) {
                "GPS: ${event.latitude}, ${event.longitude}"
            } else {
                "GPS: nincs adat"
            }

        holder.tvSyncStatus.text =
            if (event.synced) {
                "Szinkronizálva"
            } else {
                "Nincs szinkronizálva"
            }

        holder.itemView.setOnClickListener {
            openEventOnMap(
                holder,
                item
            )
        }

        if (event.photoPath != null) {

            val bitmap =
                BitmapFactory.decodeFile(
                    event.photoPath
                )

            holder.imgPhoto.setImageBitmap(
                bitmap
            )

            holder.imgPhoto.setOnClickListener {

                val intent =
                    Intent(
                        holder.itemView.context,
                        PhotoViewerActivity::class.java
                    )

                intent.putExtra(
                    "photoPath",
                    event.photoPath
                )

                holder.itemView.context
                    .startActivity(intent)
            }

        } else {

            holder.imgPhoto.setImageDrawable(
                null
            )

            holder.imgPhoto.setOnClickListener(
                null
            )
        }
    }

    override fun getItemCount(): Int =
        items.size

    private fun openEventOnMap(
        holder: EventViewHolder,
        item: EventListItem
    ) {

        val latitude =
            item.event.latitude

        val longitude =
            item.event.longitude

        if (
            latitude == null ||
            longitude == null
        ) {
            Toast.makeText(
                holder.itemView.context,
                "Ehhez az eseményhez nincs GPS adat.",
                Toast.LENGTH_SHORT
            ).show()
            return
        }

        val eventLabel =
            if (item.event.eventType == "IN") {
                "belépés"
            } else {
                "kilépés"
            }

        val label =
            Uri.encode(
                "${item.employeeName} - $eventLabel"
            )

        val mapUri =
            Uri.parse(
                "geo:$latitude,$longitude?q=$latitude,$longitude($label)"
            )

        val mapsIntent =
            Intent(
                Intent.ACTION_VIEW,
                mapUri
            ).setPackage(
                "com.google.android.apps.maps"
            )

        val context =
            holder.itemView.context

        if (mapsIntent.resolveActivity(context.packageManager) != null) {
            context.startActivity(mapsIntent)
        } else {
            context.startActivity(
                Intent(
                    Intent.ACTION_VIEW,
                    Uri.parse(
                        "https://www.google.com/maps?q=$latitude,$longitude"
                    )
                )
            )
        }
    }
}
