package hu.paksiinformatika.mobilblokkolo

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import hu.paksiinformatika.mobilblokkolo.data.local.EmployeeEntity

class EmployeeAdapter(
    private val items: List<EmployeeEntity>
) : RecyclerView.Adapter<EmployeeAdapter.EmployeeViewHolder>() {

    class EmployeeViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val tvEmployeeName: TextView =
            view.findViewById(R.id.tvEmployeeName)

        val tvEmployeeId: TextView =
            view.findViewById(R.id.tvEmployeeId)

        val tvEmployeeStatus: TextView =
            view.findViewById(R.id.tvEmployeeStatus)
    }

    override fun onCreateViewHolder(
        parent: ViewGroup,
        viewType: Int
    ): EmployeeViewHolder {

        val view = LayoutInflater
            .from(parent.context)
            .inflate(R.layout.item_employee, parent, false)

        return EmployeeViewHolder(view)
    }

    override fun onBindViewHolder(
        holder: EmployeeViewHolder,
        position: Int
    ) {

        val employee = items[position]

        holder.tvEmployeeName.text =
            employee.name

        holder.tvEmployeeId.text =
            "Azonosító: ${employee.id}"

        holder.tvEmployeeStatus.text =
            if (employee.active) {
                "Aktív"
            } else {
                "Inaktív"
            }
    }

    override fun getItemCount(): Int =
        items.size
}